<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Coupon;
use App\Jobs\CancelSingleBooking; // Add this import
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Response\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\New_movie_ticket;

class BookingController extends Controller
{
    // Lấy danh sách tất cả booking (chỉ những cái chưa bị xóa mềm)
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $bookings = Booking::with(['user', 'showtime.movie', 'showtime.room', 'bookingSeats.seat'])
            ->where('is_deleted', false)
            ->paginate($perPage);

        return ApiResponse::success($bookings, 'Lấy danh sách booking thành công');
    }

    // Tạo mới một booking
    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|string|exists:user_account,user_id',
                'showtime_id' => 'required|string|exists:showtime,showtime_id',
                'total_price' => 'numeric',
                'status' => 'in:PENDING,CONFIRMED,CANCELLED',
            ]);

            DB::beginTransaction();
            // Tạo booking
            $booking = Booking::create([
                'booking_id' => Str::uuid(),
                'user_id' => $request->user_id,
                'showtime_id' => $request->showtime_id,
                'total_price' => $request->total_price,
                'status' => $request->status ?? 'PENDING',
                'is_deleted' => false,
            ]);

            // Gán ghế nếu có
            if ($request->has('seat_ids')) {
                foreach ($request->seat_ids as $seat_id) {
                    $booking->bookingSeats()->create([
                        'booking_seat_id' => Str::uuid(),
                        'seat_id' => $seat_id,
                    ]);
                }
            }

            // Dispatch job hủy booking sau 5 phút
            CancelSingleBooking::dispatch($booking->booking_id)
                ->delay(now()->addMinutes(5));

            // Lấy thông tin suất chiếu và phim
            $showtime = $booking->showtime()->with('movie')->first();
            $movieTitle = $showtime->movie->title ?? null;

            DB::commit();

            // Trả về tất cả thuộc tính của booking và thêm movie_title
            return ApiResponse::success(
                array_merge($booking->toArray(), ['movie_title' => $movieTitle]),
                'Tạo booking thành công',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $debug = config('app.debug');
            $errorResponse = [
                'message' => 'Lỗi tạo booking: ' . $e->getMessage(),
            ];
            if ($debug) {
                $errorResponse['file'] = $e->getFile();
                $errorResponse['line'] = $e->getLine();
                $errorResponse['trace'] = collect($e->getTrace())->take(5);
            }
            return response()->json($errorResponse, 500);
        }
    }

    // Xem chi tiết một booking theo ID
    public function show($id)
    {
        $booking = Booking::with(['user', 'showtime.movie', 'showtime.room', 'bookingSeats.seat'])
            ->find($id);
        if (!$booking) {
            return ApiResponse::error('Booking không tồn tại', 404);
        }
        return ApiResponse::success($booking, 'Lấy booking thành công');
    }

    // Lấy danh sách booking theo user_id
    public function showByUserId(Request $request, $userId)
    {
        $perPage = $request->input('per_page', 10); 

        $bookings = Booking::with(['user', 'showtime.movie', 'showtime.room', 'bookingSeats.seat'])
            ->where('user_id', $userId)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        if ($bookings->isEmpty()) {
            return response()->json([
                'code' => 404,
                'message' => 'No bookings found for this user',
            ]);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách booking theo user thành công',
            'data' => $bookings,
        ]);
    }

    // Cập nhật booking (ví dụ đổi trạng thái)
    public function update(Request $request, $id)
    {
        try {
            Log::info("Updating booking ID: {$id}, Request data:", $request->all());

            $booking = Booking::find($id);
            if (!$booking) {
                Log::error("Booking not found for ID: {$id}");
                return ApiResponse::error('Booking không tồn tại', 404);
            }

            $request->validate([
                'status' => 'required|in:PENDING,CONFIRMED,CANCELLED',
            ]);

            $newStatus = $request->status;
            $oldStatus = $booking->status;

            Log::info("Booking ID: {$id}, Old Status: {$oldStatus}, New Status: {$newStatus}");

            // Update booking status
            $booking->status = $newStatus;
            $booking->save();

            // Send email if status is changed to CONFIRMED
            if ($newStatus === 'CONFIRMED' && $oldStatus !== 'CONFIRMED') {
                Log::info("Processing CONFIRMED status for booking ID: {$id}");

                // Find user
                $user = $booking->user;
                if (!$user || !$user->email) {
                    Log::error("User or user email not found for booking ID: {$id}", ['user_id' => $booking->user_id]);
                } else {
                    // Find payment
                    $payment = Payment::where('booking_id', $booking->booking_id)->first();

                    if ($payment && $payment->payment_status === 'PENDING') {
                        // Check for other COMPLETED payments
                        $existsCompleted = Payment::where('booking_id', $booking->booking_id)
                            ->where('payment_status', 'COMPLETED')
                            ->where('payment_id', '!=', $payment->payment_id)
                            ->exists();

                        if ($existsCompleted) {
                            Log::warning("Booking ID {$booking->booking_id} already has a COMPLETED payment.");
                        } else {
                            // Update payment status to COMPLETED
                            $payment->payment_status = 'COMPLETED';
                            $payment->save();
                            Log::info("Payment ID {$payment->payment_id} updated to COMPLETED for booking ID: {$booking->booking_id}");
                        }
                    } else {
                        Log::warning("No payment found for booking ID: {$booking->booking_id}, proceeding with email");
                    }

                    // Prepare email data
                    $showtime = $booking->showtime;
                    $movie = $showtime ? $showtime->movie : null;
                    $room = $showtime ? $showtime->room : null;
                    $cinema = $room ? $room->cinema : null;

                    // Get booked seats
                    $seats = $booking->bookingSeats->map(function ($bs) {
                        return $bs->seat->seat_number ?? '';
                    })->toArray();
                    $seatsString = implode(', ', $seats);

                    // Email data
                    $customerName = $user->full_name ?? 'Khách hàng';
                    $bookingId = $booking->booking_id;
                    $movieTitle = $movie ? $movie->title : 'Tên phim';
                    $cinemaName = $cinema ? $cinema->name : 'Rạp chiếu';
                    $roomName = $room ? $room->room_name : 'Phòng chiếu';
                    $showtimeStr = $showtime ? $showtime->start_time->format('d-m-Y H:i') : 'Thời gian chiếu';
                    $totalPrice = $booking->total_price ?? 0;
                    $barcode = $booking->barcode ?? '';
                    $ticketCode = $payment ? $payment->payment_id : $bookingId;

                    Log::info("Preparing email for booking ID: {$bookingId}", [
                        'email' => $user->email,
                        'customerName' => $customerName,
                        'movieTitle' => $movieTitle,
                        'showtimeStr' => $showtimeStr,
                        'barcode' => $barcode,
                        'totalPrice' => $totalPrice,
                        'ticketCode' => $ticketCode,
                        'seats' => $seatsString,
                    ]);

                    if (!$barcode) {
                        Log::warning("No barcode URL found for booking ID: {$bookingId}");
                    }

                    try {
                        // Use send() for testing to avoid queue delays
                        Mail::to($user->email)->send(new New_movie_ticket(
                            $customerName,
                            $bookingId,
                            $movieTitle,
                            $cinemaName,
                            $roomName,
                            $showtimeStr,
                            $seatsString,
                            $totalPrice,
                            $barcode,
                            $ticketCode
                        ));
                        Log::info("Confirmation email sent successfully for booking ID: {$bookingId}");
                    } catch (\Exception $e) {
                        Log::error("Failed to send confirmation email for booking ID {$booking->booking_id}: {$e->getMessage()}");
                    }
                }
            }

            return ApiResponse::success($booking, 'Cập nhật trạng thái thành công');
        } catch (\Exception $e) {
            $debug = config('app.debug');
            $errorResponse = [
                'message' => 'Lỗi cập nhật booking: ' . $e->getMessage(),
            ];
            if ($debug) {
                $errorResponse['file'] = $e->getFile();
                $errorResponse['line'] = $e->getLine();
                $errorResponse['trace'] = collect($e->getTrace())->take(5);
            }
            return response()->json($errorResponse, 500);
        }
    }

    // Cập nhật tổng giá tiền của booking (nếu chưa thanh toán)
    public function updateTotalPrice(Request $request, $id)
    {
        try {
            $booking = Booking::find($id);
            if (!$booking) {
                return ApiResponse::error('Booking không tồn tại', 404);
            }

            $hasCompletedPayment = Payment::where('booking_id', $booking->booking_id)
                ->where('payment_status', 'COMPLETED')
                ->exists();

            if ($hasCompletedPayment) {
                return ApiResponse::error('Booking đã thanh toán, không thể cập nhật giá tiền', 403);
            }

            $request->validate([
                'total_price' => 'required|numeric|min:0',
            ]);

            $booking->total_price = $request->total_price;
            $booking->save();

            return ApiResponse::success($booking, 'Cập nhật giá tiền thành công');
        } catch (\Exception $e) {
            $debug = config('app.debug');
            $errorResponse = [
                'message' => 'Lỗi cập nhật giá tiền: ' . $e->getMessage(),
            ];
            if ($debug) {
                $errorResponse['file'] = $e->getFile();
                $errorResponse['line'] = $e->getLine();
                $errorResponse['trace'] = collect($e->getTrace())->take(5);
            }
            return response()->json($errorResponse, 500);
        }
    }

    // Cập nhật orderCode của booking (nếu chưa thanh toán)
    public function updateOrderCode(Request $request, $id)
    {
        try {
            $booking = Booking::find($id);
            if (!$booking) {
                return ApiResponse::error('Booking không tồn tại', 404);
            }

            $hasCompletedPayment = Payment::where('booking_id', $booking->booking_id)
                ->where('payment_status', 'COMPLETED')
                ->exists();

            if ($hasCompletedPayment) {
                return ApiResponse::error('Booking đã thanh toán, không thể cập nhật orderCode', 403);
            }

            $request->validate([
                'order_code' => 'required|string|max:255',
            ]);

            $booking->order_code = $request->order_code;
            $booking->save();

            return ApiResponse::success($booking, 'Cập nhật orderCode thành công');
        } catch (\Exception $e) {
            $debug = config('app.debug');
            $errorResponse = [
                'message' => 'Lỗi cập nhật orderCode: ' . $e->getMessage(),
            ];
            if ($debug) {
                $errorResponse['file'] = $e->getFile();
                $errorResponse['line'] = $e->getLine();
                $errorResponse['trace'] = collect($e->getTrace())->take(5);
            }
            return response()->json($errorResponse, 500);
        }
    }

    public function updateCoupon(Request $request, $booking_id)
    {
        try {
            $request->validate([
                'coupon_code' => 'nullable|string|exists:coupon,code', // Fixed table name to 'coupons'
            ]);

            // Find the booking
            $booking = Booking::where('booking_id', $booking_id)
                ->where('is_deleted', false)
                ->first();

            if (!$booking) {
                return ApiResponse::error('Booking not found', 404);
            }

            // Check if the booking is in PENDING status
            if ($booking->status !== 'PENDING') {
                return ApiResponse::error('Cannot update coupon: Booking is not in PENDING status', 400);
            }

            $coupon = null;
            $couponCode = $request->input('coupon_code');

            if ($couponCode) {
                $coupon = Coupon::where('code', $couponCode)
                    ->where('is_active', true)
                    ->whereColumn('is_used', '<', 'quantity')
                    ->where('expiry_date', '>=', Carbon::now())
                    ->first();

                if (!$coupon) {
                    return ApiResponse::error('Invalid or unusable coupon', 400);
                }
            }

            // Update the coupon_id
            $booking->coupon_id = $coupon ? $coupon->coupon_id : null;
            $booking->save();

            return ApiResponse::success($booking, 'Coupon updated successfully');
        } catch (\Exception $e) {
            $debug = config('app.debug');
            $errorResponse = [
                'message' => 'Lỗi cập nhật coupon: ' . $e->getMessage(),
            ];
            if ($debug) {
                $errorResponse['file'] = $e->getFile();
                $errorResponse['line'] = $e->getLine();
                $errorResponse['trace'] = collect($e->getTrace())->take(5);
            }
            return response()->json($errorResponse, 500);
        }
    }

    public function updateBarcode(Request $request, $bookingId)
    {
        try {
            $request->validate([
                'barcode' => 'required|url',
            ]);

            $booking = Booking::where('booking_id', $bookingId)->first();
            if (!$booking) {
                return ApiResponse::error('booking không tồn tại', 404);
            }

            $booking->barcode = $request->barcode;
            $booking->save();

            return ApiResponse::success($booking, 'Cập nhật barcode URL thành công');
        } catch (\Exception $e) {
            $debug = config('app.debug');
            $errorResponse = [
                'message' => 'Lỗi cập nhật barcode: ' . $e->getMessage(),
            ];
            if ($debug) {
                $errorResponse['file'] = $e->getFile();
                $errorResponse['line'] = $e->getLine();
                $errorResponse['trace'] = collect($e->getTrace())->take(5);
            }
            return response()->json($errorResponse, 500);
        }
    }

    // Tìm kiếm booking theo số điện thoại hoặc tên người dùng
    public function searchBooking(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
        ]);

        $keyword = $request->input('keyword');

        $query = Booking::with(['user', 'showtime.movie', 'showtime.room', 'bookingSeats.seat'])
            ->where('is_deleted', false)
            ->whereHas('user', function ($q) use ($keyword) {
                $q->where('phone', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('username', 'LIKE', '%' . $keyword . '%');
            });

        $perPage = $request->input('per_page', 10);
        $results = $query->paginate($perPage);

        return ApiResponse::success($results, 'Tìm kiếm booking theo số điện thoại hoặc tên người dùng thành công');
    }

    // Xóa mềm một booking (chuyển is_deleted = true)
    public function destroy($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return ApiResponse::error('Booking không tồn tại', 404);
        }

        $booking->is_deleted = true;
        $booking->save();

        return ApiResponse::success(null, 'Xóa booking thành công');
    }

    // Khôi phục một booking đã bị xóa mềm
    public function restore($id)
    {
        $booking = Booking::where('booking_id', $id)
            ->where('is_deleted', true)
            ->first();

        if (!$booking) {
            return ApiResponse::error('Không tìm thấy booking đã bị xóa mềm', 404);
        }

        $booking->is_deleted = false;
        $booking->save();

        return ApiResponse::success($booking, 'Khôi phục booking thành công');
    }
}
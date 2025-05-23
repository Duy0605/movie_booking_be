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

    // Tạo mới một booking và gán ghế (nếu có)
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string|exists:user_account,user_id',
            'showtime_id' => 'required|string|exists:showtime,showtime_id',
            'total_price' => 'numeric',
            'status' => 'in:PENDING,CONFIRMED,CANCELLED',
        ]);

        DB::beginTransaction();
        try {
            $booking = Booking::create([
                'booking_id' => Str::uuid(),
                'user_id' => $request->user_id,
                'showtime_id' => $request->showtime_id,
                'total_price' => $request->total_price,
                'status' => $request->status ?? 'PENDING',
                'is_deleted' => false,
            ]);

            if ($request->has('seat_ids')) {
                foreach ($request->seat_ids as $seat_id) {
                    $booking->bookingSeats()->create([
                        'booking_seat_id' => Str::uuid(),
                        'seat_id' => $seat_id,
                    ]);
                }
            }

            // Dispatch the CancelSingleBooking job with a 5-minute delay for this specific booking
            CancelSingleBooking::dispatch($booking->booking_id)
                ->delay(now()->addMinutes(5));

            DB::commit();
            return ApiResponse::success($booking, 'Tạo booking thành công', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Lỗi tạo booking', 500, $e->getMessage());
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
        $booking = Booking::find($id);
        if (!$booking) {
            return ApiResponse::error('Booking không tồn tại', 404);
        }

        $request->validate([
            'status' => 'in:PENDING,CONFIRMED,CANCELLED',
        ]);

        $booking->status = $request->status ?? $booking->status;
        $booking->save();

        return ApiResponse::success($booking, 'Cập nhật trạng thái thành công');
    }

    // Cập nhật tổng giá tiền của booking (nếu chưa thanh toán)
    public function updateTotalPrice(Request $request, $id)
    {
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
    }

    // Cập nhật orderCode của booking (nếu chưa thanh toán)
    public function updateOrderCode(Request $request, $id)
    {
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
    }

    public function updateCoupon(Request $request, $booking_id)
{
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
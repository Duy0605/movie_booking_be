<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Payment;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // Lấy danh sách booking
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Số bản ghi/trang, mặc định 10
        $bookings = Booking::with(['user', 'showtime.movie', 'bookingSeats'])
            ->where('is_deleted', false)
            ->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách booking thành công',
            'data' => $bookings
        ]);
    }


    // Tạo booking mới
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

            DB::commit();
            return response()->json(['code' => 201, 'message' => 'Tạo booking thành công', 'data' => $booking]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['code' => 500, 'message' => 'Lỗi tạo booking', 'error' => $e->getMessage()]);
        }
    }

    // Xem chi tiết booking
    public function show($id)
    {
        $booking = Booking::with(['user', 'showtime.movie', 'bookingSeats.seat'])->find($id);
        if (!$booking) {
            return response()->json(['code' => 404, 'message' => 'Booking not found']);
        }
        return response()->json(['code' => 200, 'message' => 'Success', 'data' => $booking]);
    }

    // Cập nhật booking (ví dụ đổi trạng thái)
    public function update(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['code' => 404, 'message' => 'Booking not found']);
        }

        $request->validate([
            'status' => 'in:PENDING,CONFIRMED,CANCELLED',
        ]);

        $booking->status = $request->status ?? $booking->status;
        $booking->save();

        return response()->json(['code' => 200, 'message' => 'Booking updated', 'data' => $booking]);
    }

    // cap nhat gia tien
    public function updateTotalPrice(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['code' => 404, 'message' => 'Booking không tồn tại']);
        }

        // Kiểm tra nếu đã có payment COMPLETED
        $hasCompletedPayment = Payment::where('booking_id', $booking->booking_id)
            ->where('payment_status', 'COMPLETED')
            ->exists();

        if ($hasCompletedPayment) {
            return response()->json(['code' => 403, 'message' => 'Booking đã thanh toán, không thể cập nhật giá tiền']);
        }

        $request->validate([
            'total_price' => 'required|numeric|min:0',
        ]);

        $booking->total_price = $request->total_price;
        $booking->save();

        return response()->json(['code' => 200, 'message' => 'Cập nhật giá tiền thành công', 'data' => $booking]);
    }

    // Xóa booking (soft delete)
    public function destroy($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['code' => 404, 'message' => 'Booking not found']);
        }

        $booking->is_deleted = true;
        $booking->save();

        return response()->json(['code' => 200, 'message' => 'Booking deleted']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookingSeat;
use App\Models\Booking;
use App\Models\Seat;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\ShowTime;

class BookingSeatController extends Controller
{
    // Lấy danh sách booking seats chưa bị xóa
    public function index()
    {
        $bookingSeats = BookingSeat::with(['booking', 'seat'])
            ->where('is_deleted', false)
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách booking seats thành công',
            'data' => $bookingSeats
        ]);
    }

    // Tạo mới booking seat
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|string|exists:booking,booking_id',
            'seat_id' => 'required|string|exists:seat,seat_id',
        ]);

        // Kiểm tra xem booking và seat có còn hợp lệ (chưa bị xóa)
        $booking = Booking::where('booking_id', $request->booking_id)
            ->where('is_deleted', false)
            ->first();
        $seat = Seat::where('seat_id', $request->seat_id)
            ->where('is_deleted', false)
            ->first();

        if (!$booking) {
            return response()->json(['code' => 404, 'message' => 'Booking không tồn tại hoặc đã bị xóa']);
        }
        if (!$seat) {
            return response()->json(['code' => 404, 'message' => 'Seat không tồn tại hoặc đã bị xóa']);
        }

        // Lấy showtime của booking
        $showtime = ShowTime::where('showtime_id', $booking->showtime_id)
            ->where('is_deleted', false)
            ->first();

        if (!$showtime) {
            return response()->json(['code' => 404, 'message' => 'Suất chiếu không tồn tại hoặc đã bị xóa']);
        }

        // Kiểm tra ghế có thuộc phòng của suất chiếu không
        if ($seat->room_id !== $showtime->room_id) {
            return response()->json([
                'code' => 400,
                'message' => 'Ghế không thuộc phòng của suất chiếu này'
            ]);
        }

        // Kiểm tra seat đã được đặt trong booking khác chưa (cùng showtime)
        $exists = BookingSeat::where('seat_id', $request->seat_id)
            ->whereHas('booking', function ($query) use ($booking) {
                $query->where('showtime_id', $booking->showtime_id)
                    ->whereIn('status', ['PENDING', 'CONFIRMED'])
                    ->where('is_deleted', false);
            })->where('is_deleted', false)
            ->exists();

        if ($exists) {
            return response()->json([
                'code' => 409,
                'message' => 'Ghế này đã được đặt cho suất chiếu tương ứng rồi'
            ]);
        }

        $bookingSeat = BookingSeat::create([
            'booking_seat_id' => Str::uuid()->toString(),
            'booking_id' => $request->booking_id,
            'seat_id' => $request->seat_id,
            'is_deleted' => false
        ]);
        $booking->updateTotalPrice();
        return response()->json([
            'code' => 201,
            'message' => 'Tạo booking seat thành công',
            'data' => $bookingSeat
        ]);
    }
    // Lấy chi tiết booking seat theo id
    public function show($id)
    {
        try {
            $bookingSeat = BookingSeat::with(['booking', 'seat'])
                ->where('booking_seat_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            return response()->json([
                'code' => 200,
                'message' => 'Lấy thông tin booking seat thành công',
                'data' => $bookingSeat
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Booking seat không tồn tại'
            ]);
        }
    }

    // Cập nhật booking seat
    public function update(Request $request, $id)
    {
        try {
            $bookingSeat = BookingSeat::where('booking_seat_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            $request->validate([
                'booking_id' => 'sometimes|string|exists:booking,booking_id',
                'seat_id' => 'sometimes|string|exists:seat,seat_id',
            ]);

            if ($request->has('booking_id')) {
                $booking = Booking::where('booking_id', $request->booking_id)
                    ->where('is_deleted', false)
                    ->first();

                if (!$booking) {
                    return response()->json(['code' => 404, 'message' => 'Booking không tồn tại hoặc đã bị xóa']);
                }

                $bookingSeat->booking_id = $request->booking_id;
            }

            if ($request->has('seat_id')) {
                $seat = Seat::where('seat_id', $request->seat_id)
                    ->where('is_deleted', false)
                    ->first();

                if (!$seat) {
                    return response()->json(['code' => 404, 'message' => 'Seat không tồn tại hoặc đã bị xóa']);
                }

                // Kiểm tra seat đã được đặt cho suất chiếu booking hiện tại chưa
                $exists = BookingSeat::where('seat_id', $request->seat_id)
                    ->where('booking_seat_id', '!=', $id)
                    ->whereHas('booking', function ($query) use ($bookingSeat) {
                        $query->where('showtime_id', $bookingSeat->booking->showtime_id)
                            ->where('status', 'CONFIRMED')
                            ->where('is_deleted', false);
                    })->where('is_deleted', false)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'code' => 409,
                        'message' => 'Ghế này đã được đặt cho suất chiếu tương ứng'
                    ]);
                }

                $bookingSeat->seat_id = $request->seat_id;
            }

            $bookingSeat->save();

            return response()->json([
                'code' => 200,
                'message' => 'Cập nhật booking seat thành công',
                'data' => $bookingSeat
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['code' => 404, 'message' => 'Booking seat không tồn tại']);
        }
    }

    // Xóa mềm booking seat (update is_deleted = true)
    public function destroy($id)
    {
        try {
            $bookingSeat = BookingSeat::where('booking_seat_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            $bookingSeat->is_deleted = true;
            $bookingSeat->save();

            return response()->json([
                'code' => 200,
                'message' => 'Xóa booking seat thành công (soft delete)'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['code' => 404, 'message' => 'Booking seat không tồn tại']);
        }
    }

    public function forceDelete($id)
    {
        try {
            $bookingSeat = BookingSeat::where('booking_seat_id', $id)->firstOrFail();
            $bookingSeat->delete(); // Xóa khỏi DB
            return response()->json([
                'code' => 200,
                'message' => 'Xóa booking seat vĩnh viễn thành công'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Booking seat không tồn tại'
            ]);
        }
    }
    public function getSeatsByShowtime($showtimeId)
    {
        // Lấy suất chiếu
        $showtime = ShowTime::where('showtime_id', $showtimeId)->where('is_deleted', false)->first();
        if (!$showtime) {
            return response()->json(['code' => 404, 'message' => 'Suất chiếu không tồn tại']);
        }

        // Lấy tất cả ghế của phòng chiếu
        $seats = Seat::where('room_id', $showtime->room_id)
            ->where('is_deleted', false)
            ->get();

        // Lấy danh sách seat_id đã được đặt cho suất chiếu này
        $bookedSeatIds = BookingSeat::whereHas('booking', function ($query) use ($showtimeId) {
            $query->where('showtime_id', $showtimeId)
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->where('is_deleted', false);
        })
            ->where('is_deleted', false)
            ->pluck('seat_id')
            ->toArray();
        // dd($bookedSeatIds);
        // Gắn trạng thái đã đặt hay chưa cho mỗi ghế
        $seats = $seats->map(function ($seat) use ($bookedSeatIds) {
            $seat->is_booked = in_array($seat->seat_id, $bookedSeatIds);
            return $seat;
        });

        return response()->json([
            'code' => 200,
            'message' => 'Danh sách ghế theo suất chiếu',
            'data' => $seats
        ]);
    }
}

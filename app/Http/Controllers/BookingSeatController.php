<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookingSeat;
use App\Models\Booking;
use App\Models\Seat;
use App\Events\SeatBookedEvent;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\ShowTime;
use App\Response\ApiResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;

class BookingSeatController extends Controller
{
    /**
     * Lấy danh sách booking seats chưa bị xóa (is_deleted = false)
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $bookingSeats = BookingSeat::with(['booking', 'seat'])
            ->where('is_deleted', false)
            ->paginate($perPage);

        return ApiResponse::success($bookingSeats, 'Lấy danh sách booking seats thành công');
    }

    /**
     * Tạo mới booking seat
     */
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|string|exists:bookings,booking_id',
            'seat_id' => 'required|string|exists:seats,seat_id',
        ]);

        // Log broadcast configuration
        Log::info('Broadcast Configuration', [
            'BROADCAST_CONNECTION' => config('broadcasting.default'),
            'PUSHER_APP_KEY' => env('PUSHER_APP_KEY'),
            'PUSHER_APP_ID' => env('PUSHER_APP_ID'),
            'PUSHER_CLUSTER' => env('PUSHER_APP_CLUSTER'),
            'PUSHER_HOST' => env('PUSHER_HOST', 'default'),
            'PUSHER_PORT' => env('PUSHER_PORT'),
            'PUSHER_SCHEME' => env('PUSHER_SCHEME'),
            'BROADCASTER' => Broadcast::driver() ? get_class(Broadcast::driver()) : 'None',
            'BROADCAST_DRIVER_ENV' => env('BROADCAST_CONNECTION'),
            'AVAILABLE_DRIVERS' => array_keys(config('broadcasting.connections')),
            'PUSHER_CONFIG' => config('broadcasting.connections.pusher', 'Not defined')
        ]);

        $booking = Booking::where('booking_id', $request->booking_id)
            ->where('is_deleted', false)
            ->first();
        $seat = Seat::where('seat_id', $request->seat_id)
            ->where('is_deleted', false)
            ->first();

        if (!$booking) {
            return ApiResponse::error('Booking không tồn tại hoặc đã bị xóa', 404);
        }

        if (!$seat) {
            return ApiResponse::error('Seat không tồn tại hoặc đã bị xóa', 404);
        }

        $showtime = ShowTime::where('showtime_id', $booking->showtime_id)
            ->where('is_deleted', false)
            ->first();
        if (!$showtime) {
            return ApiResponse::error('Suất chiếu không tồn tại hoặc đã bị xóa', 404);
        }

        if ($seat->room_id !== $showtime->room_id) {
            Log::error('Room mismatch in store', [
                'seat_id' => $request->seat_id,
                'seat_room_id' => $seat->room_id,
                'showtime_room_id' => $showtime->room_id,
                'showtime_id' => $booking->showtime_id
            ]);
            return ApiResponse::error('Ghế không thuộc phòng của suất chiếu này', 400);
        }

        // Check Redis lock
        $lockKey = "seat_lock:{$showtime->showtime_id}:{$request->seat_id}";
        $lockedBy = Redis::get($lockKey);
        if ($lockedBy && $lockedBy !== $request->booking_id) {
            return ApiResponse::error('Ghế đang được giữ bởi người dùng khác', 409);
        }

        try {
            DB::beginTransaction();

            // Check if seat is already booked
            $exists = BookingSeat::where('seat_id', $request->seat_id)
                ->whereHas('booking', function ($query) use ($booking) {
                    $query->where('showtime_id', $booking->showtime_id)
                        ->whereIn('status', ['PENDING', 'CONFIRMED'])
                        ->where('is_deleted', false);
                })->where('is_deleted', false)->exists();

            if ($exists) {
                Redis::del($lockKey);
                return ApiResponse::error('Ghế này đã được đặt cho suất chiếu tương ứng rồi', 409);
            }

            $bookingSeat = BookingSeat::create([
                'booking_seat_id' => Str::uuid()->toString(),
                'booking_id' => $request->booking_id,
                'seat_id' => $request->seat_id,
                'is_deleted' => false,
            ]);

            $booking->updateTotalPrice();

            // Broadcast seat booked event
            try {
                broadcast(new SeatBookedEvent($showtime->showtime_id, $seat->seat_id, $seat->seat_number, $request->booking_id))->toOthers();
            } catch (\Exception $e) {
                Log::error('Broadcasting SeatBookedEvent failed', ['error' => $e->getMessage()]);
                // Continue with booking even if broadcast fails
            }

            DB::commit();
            Redis::del($lockKey);

            return ApiResponse::success($bookingSeat, 'Tạo booking seat thành công', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Redis::del($lockKey);
            Log::error('Failed to create booking seat', ['error' => $e->getMessage()]);
            return ApiResponse::error('Lỗi khi tạo booking seat: ' . $e->getMessage(), 500);
        }
    }

    public function lockSeat(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|string|exists:booking,booking_id',
            'seat_number' => 'required|string',
            'showtime_id' => 'required|string|exists:showtime,showtime_id',
        ]);

        $booking = Booking::where('booking_id', $request->booking_id)
            ->where('is_deleted', false)
            ->first();
        if (!$booking) {
            return ApiResponse::error('Booking không tồn tại hoặc đã bị xóa', 404);
        }

        $showtime = ShowTime::where('showtime_id', $request->showtime_id)
            ->where('is_deleted', false)
            ->first();
        if (!$showtime) {
            return ApiResponse::error('Suất chiếu không tồn tại hoặc đã bị xóa', 404);
        }

        $seat = Seat::where('seat_number', $request->seat_number)
            ->where('room_id', $showtime->room_id)
            ->where('is_deleted', false)
            ->first();
        if (!$seat) {
            \Log::error('Seat not found in room', [
                'seat_number' => $request->seat_number,
                'room_id' => $showtime->room_id,
                'showtime_id' => $request->showtime_id
            ]);
            return ApiResponse::error('Ghế không tồn tại trong phòng của suất chiếu này', 404);
        }

        $lockKey = "seat_lock:{$request->showtime_id}:{$seat->seat_id}";
        $lockedBy = Redis::get($lockKey);

        if ($lockedBy) {
            if ($lockedBy === $request->booking_id) {
                // Same user is toggling the seat, so unlock it
                Redis::del($lockKey);
                return ApiResponse::success(null, 'Ghế đã được mở khóa', 200);
            }
            return ApiResponse::error('Ghế đang được giữ bởi người dùng khác', 409);
        }

        // Lock the seat
        Redis::setex($lockKey, 600, $request->booking_id);
        return ApiResponse::success(null, 'Ghế đã được khóa tạm thời', 200);
    }

    public function unlockSeat(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|string|exists:booking,booking_id',
            'seat_number' => 'required|string',
            'showtime_id' => 'required|string|exists:showtime,showtime_id',
        ]);

        $booking = Booking::where('booking_id', $request->booking_id)
            ->where('is_deleted', false)
            ->first();
        if (!$booking) {
            return ApiResponse::error('Booking không tồn tại hoặc đã bị xóa', 404);
        }

        $showtime = ShowTime::where('showtime_id', $request->showtime_id)
            ->where('is_deleted', false)
            ->first();
        if (!$showtime) {
            return ApiResponse::error('Suất chiếu không tồn tại hoặc đã bị xóa', 404);
        }

        $seat = Seat::where('seat_number', $request->seat_number)
            ->where('room_id', $showtime->room_id)
            ->where('is_deleted', false)
            ->first();
        if (!$seat) {
            \Log::error('Seat not found for unlock', [
                'seat_number' => $request->seat_number,
                'room_id' => $showtime->room_id,
                'showtime_id' => $request->showtime_id
            ]);
            return ApiResponse::error('Ghế không tồn tại trong phòng của suất chiếu này', 404);
        }

        $lockKey = "seat_lock:{$request->showtime_id}:{$seat->seat_id}";
        $lockedBy = Redis::get($lockKey);

        if (!$lockedBy) {
            return ApiResponse::success(null, 'Ghế không được khóa', 200);
        }

        if ($lockedBy !== $request->booking_id) {
            return ApiResponse::error('Bạn không có quyền mở khóa ghế này', 403);
        }

        Redis::del($lockKey);
        return ApiResponse::success(null, 'Ghế đã được mở khóa', 200);
    }

    /**
     * Lấy chi tiết booking seat theo ID
     */
    public function show($id)
    {
        try {
            $bookingSeat = BookingSeat::with(['booking', 'seat'])
                ->where('booking_seat_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            return ApiResponse::success($bookingSeat, 'Lấy thông tin booking seat thành công');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Booking seat không tồn tại', 404);
        }
    }

    /**
     * Cập nhật booking seat
     */
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
                    ->where('is_deleted', false)->first();

                if (!$booking) {
                    return ApiResponse::error('Booking không tồn tại hoặc đã bị xóa', 404);
                }

                $bookingSeat->booking_id = $request->booking_id;
            }

            if ($request->has('seat_id')) {
                $seat = Seat::where('seat_id', $request->seat_id)
                    ->where('is_deleted', false)->first();

                if (!$seat) {
                    return ApiResponse::error('Seat không tồn tại hoặc đã bị xóa', 404);
                }


                $exists = BookingSeat::where('seat_id', $request->seat_id)
                    ->where('booking_seat_id', '!=', $id)
                    ->whereHas('booking', function ($query) use ($bookingSeat) {
                        $query->where('showtime_id', $bookingSeat->booking->showtime_id)
                            ->where('status', 'CONFIRMED')
                            ->where('is_deleted', false);
                    })->where('is_deleted', false)->exists();

                if ($exists) {
                    return ApiResponse::error('Ghế này đã được đặt cho suất chiếu tương ứng', 409);
                }

                $bookingSeat->seat_id = $request->seat_id;
            }

            $bookingSeat->save();

            return ApiResponse::success($bookingSeat, 'Cập nhật booking seat thành công');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Booking seat không tồn tại', 404);
        }
    }

    /**
     * Xóa mềm booking seat
     */
    public function destroy($id)
    {
        try {
            $bookingSeat = BookingSeat::where('booking_seat_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            $bookingSeat->is_deleted = true;
            $bookingSeat->save();

            return ApiResponse::success(null, 'Xóa booking seat thành công (soft delete)');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Booking seat không tồn tại', 404);
        }
    }


    //Xóa vĩnh viễn booking seat

    public function forceDelete($id)
    {
        try {
            $bookingSeat = BookingSeat::where('booking_seat_id', $id)->firstOrFail();
            $bookingSeat->delete();

            return ApiResponse::success(null, 'Xóa booking seat vĩnh viễn thành công');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Booking seat không tồn tại', 404);
        }
    }


    //Khôi phục booking seat đã bị xóa mềm

    public function restore($id)
    {
        try {
            $bookingSeat = BookingSeat::where('booking_seat_id', $id)
                ->where('is_deleted', true)
                ->firstOrFail();

            $bookingSeat->is_deleted = false;
            $bookingSeat->save();

            return ApiResponse::success($bookingSeat, 'Khôi phục booking seat thành công');
        } catch (ModelNotFoundException $e) {
            return ApiResponse::error('Booking seat không tồn tại hoặc chưa bị xóa mềm', 404);
        }
    }


    //Lấy danh sách ghế theo suất chiếu (kèm trạng thái đã đặt hay chưa)

    public function getSeatsByShowtime($showtimeId)
    {
        $showtime = ShowTime::where('showtime_id', $showtimeId)->where('is_deleted', false)->first();
        if (!$showtime) {
            return ApiResponse::error('Suất chiếu không tồn tại', 404);
        }

        $perPage = request()->input('per_page', 1000);

        $seats = Seat::where('room_id', $showtime->room_id)
            ->where('is_deleted', false)
            ->paginate($perPage);


        $bookedSeatIds = BookingSeat::whereHas('booking', function ($query) use ($showtimeId) {
            $query->where('showtime_id', $showtimeId)
                ->whereIn('status', ['PENDING', 'CONFIRMED'])
                ->where('is_deleted', false);
        })->where('is_deleted', false)
            ->pluck('seat_id')
            ->toArray();

        $seatsCollection = $seats->getCollection()->map(function ($seat) use ($bookedSeatIds) {
            $seat->is_booked = in_array($seat->seat_id, $bookedSeatIds);
            return $seat;
        });

        $seats->setCollection($seatsCollection);

        return ApiResponse::success($seats, 'Danh sách ghế theo suất chiếu');
    }

}

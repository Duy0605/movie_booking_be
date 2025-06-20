<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seat;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Response\ApiResponse;
use Illuminate\Validation\Rule;

class SeatController extends Controller
{
    // Lấy danh sách ghế chưa bị xóa
    public function index()
    {
        $seats = Seat::where('is_deleted', false)->get();
        return ApiResponse::success($seats, 'Danh sách ghế');
    }

    // Tạo mới một ghế
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|string',
            'seat_number' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z]{1}[1-9][0-9]*$/',
                Rule::unique('seat')->where(function ($query) use ($request) {
                    return $query->where('room_id', $request->room_id)->where('is_deleted', false);
                }),
            ],
            'seat_type' => 'in:STANDARD,VIP,COUPLE,UNAVAILABLE',
        ], [
            'seat_number.required' => 'Vui lòng nhập số ghế.',
            'seat_number.max' => 'Số ghế không được vượt quá 10 ký tự.',
            'seat_number.regex' => 'Số ghế phải theo định dạng: một chữ cái in hoa (A-Z) theo sau là số bắt đầu từ 1 (ví dụ: A1, B12).',
            'seat_number.unique' => 'Số ghế đã tồn tại trong phòng này.',
            'seat_type.in' => 'Loại ghế không hợp lệ (chỉ được STANDARD, VIP, COUPLE, hoặc UNAVAILABLE).',
        ]);

        // Kiểm tra xem ghế đã tồn tại với is_deleted=1
        $existingSeat = Seat::where('room_id', $request->room_id)
            ->where('seat_number', $request->seat_number)
            ->where('is_deleted', true)
            ->first();

        if ($existingSeat) {
            // Khôi phục ghế đã bị xóa mềm
            $existingSeat->is_deleted = false;
            $existingSeat->seat_type = $request->seat_type ?? 'STANDARD';
            $existingSeat->save();
            return ApiResponse::success($existingSeat, 'Khôi phục ghế thành công', 200);
        }

        // Tạo ghế mới nếu không tìm thấy ghế đã xóa
        $seat = Seat::create([
            'seat_id' => (string) Str::uuid(),
            'room_id' => $request->room_id,
            'seat_number' => $request->seat_number,
            'seat_type' => $request->seat_type ?? 'STANDARD',
        ]);

        return ApiResponse::success($seat, 'Tạo ghế thành công', 201);
    }

    // Lấy thông tin một ghế cụ thể
    public function show($id)
    {
        $seat = Seat::where('seat_id', $id)->where('is_deleted', false)->first();

        if (!$seat) {
            return ApiResponse::error('Seat not found', 404);
        }

        return ApiResponse::success($seat, 'Lấy thông tin ghế thành công');
    }

    // Lấy danh sách ghế theo room_id
    public function showSeatByRoomId($id)
    {
        $seats = Seat::where('room_id', $id)
            ->where('is_deleted', false)
            ->get();

        if ($seats->isEmpty()) {
            return ApiResponse::error('Không tìm thấy ghế nào trong phòng này', 404);
        }

        return ApiResponse::success($seats, 'Lấy danh sách ghế thành công');
    }

    // Cập nhật thông tin ghế
    public function update(Request $request, $id)
    {
        $seat = Seat::find($id);

        if (!$seat || $seat->is_deleted) {
            return ApiResponse::error('Không tìm thấy ghế', 404);
        }

        $request->validate([
            'seat_number' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z]{1}[1-9][0-9]*$/',
                Rule::unique('seat')->where(function ($query) use ($seat) {
                    return $query->where('room_id', $seat->room_id)->where('is_deleted', false);
                })->ignore($seat->seat_id, 'seat_id'),
            ],
            'seat_type' => 'sometimes|in:STANDARD,VIP,COUPLE,UNAVAILABLE',
        ], [
            'seat_number.required' => 'Vui lòng nhập số ghế.',
            'seat_number.max' => 'Số ghế không được vượt quá 10 ký tự.',
            'seat_number.regex' => 'Số ghế phải theo định dạng: một chữ cái in hoa (A-Z) theo sau là số bắt đầu từ 1 (ví dụ: A1, B12).',
            'seat_number.unique' => 'Số ghế đã tồn tại trong phòng này.',
            'seat_type.in' => 'Loại ghế không hợp lệ (chỉ được STANDARD, VIP, COUPLE, hoặc UNAVAILABLE).',
        ]);

        $seat->update($request->only(['seat_number', 'seat_type']));

        return ApiResponse::success($seat, 'Cập nhật ghế thành công');
    }

    // Xóa mềm
    public function softDelete($id)
    {
        $seat = Seat::find($id);
        if (!$seat || $seat->is_deleted) {
            return ApiResponse::error('Ghế không tồn tại hoặc đã bị xóa', 404);
        }

        $seat->is_deleted = true;
        $seat->save();

        return ApiResponse::success(null, 'Ghế đã được xóa mềm');
    }

    // Khôi phục ghế
    public function restore($id)
    {
        $seat = Seat::find($id);
        if (!$seat || !$seat->is_deleted) {
            return ApiResponse::error('Ghế đã được khôi phục', 404);
        }

        $seat->is_deleted = false;
        $seat->save();

        return ApiResponse::success(null, 'Ghế đã được khôi phục');
    }

    // Xóa vĩnh viễn
    public function destroy($id)
    {
        $seat = Seat::find($id);
        if (!$seat) {
            return ApiResponse::error('Seat not found', 404);
        }

        $seat->delete();
        return ApiResponse::success(null, 'Ghế đã bị xóa vĩnh viễn');
    }

    // Tạo mới nhiều ghế
    public function storeMultiple(Request $request)
    {
        $request->validate([
            'room_id' => 'required|string',
            'prefix' => [
                'required',
                'string',
                'regex:/^[A-Z]{1}$/', // Chỉ cho phép 1 ký tự in hoa duy nhất
            ],
            'start_index' => 'required|integer|min:1',
            'end_index' => 'required|integer|gte:start_index',
            'seat_type' => 'in:STANDARD,VIP,COUPLE',
        ], [
            'room_id.required' => 'Vui lòng nhập room_id.',
            'prefix.required' => 'Vui lòng nhập tiền tố tên ghế.',
            'prefix.regex' => 'Tiền tố chỉ được là 1 ký tự in hoa (A-Z).',
            'start_index.required' => 'Vui lòng nhập chỉ số bắt đầu.',
            'end_index.required' => 'Vui lòng nhập chỉ số kết thúc.',
            'end_index.gte' => 'Chỉ số kết thúc phải lớn hơn hoặc bằng bắt đầu.',
        ]);

        $roomId = $request->room_id;
        $prefix = $request->prefix;
        $start = $request->start_index;
        $end = $request->end_index;
        $seatType = $request->seat_type ?? 'STANDARD';

        $seatsToCreate = [];
        $seatsToRestore = [];

        for ($i = $start; $i <= $end; $i++) {
            $seatNumber = $prefix . $i;

            // Không cho phép định dạng như A01, AA5, a1,...
            if (!preg_match('/^[A-Z]{1}[1-9][0-9]*$/', $seatNumber)) {
                continue; // Bỏ qua ghế không đúng định dạng
            }

            // Kiểm tra ghế đã tồn tại
            $existingSeat = Seat::where('room_id', $roomId)
                ->where('seat_number', $seatNumber)
                ->first();

            if ($existingSeat) {
                if ($existingSeat->is_deleted) {
                    // Thêm vào danh sách ghế cần khôi phục
                    $seatsToRestore[] = $existingSeat;
                }
                continue; // Bỏ qua ghế đã tồn tại (bao gồm cả ghế không bị xóa)
            }

            // Thêm vào danh sách ghế cần tạo mới
            $seatsToCreate[] = [
                'seat_id' => (string) Str::uuid(),
                'room_id' => $roomId,
                'seat_number' => $seatNumber,
                'seat_type' => $seatType,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Khôi phục các ghế đã bị xóa mềm
        foreach ($seatsToRestore as $seat) {
            $seat->is_deleted = false;
            $seat->seat_type = $seatType;
            $seat->save();
        }

        // Tạo mới các ghế
        if (!empty($seatsToCreate)) {
            Seat::insert($seatsToCreate);
        }

        $totalProcessed = count($seatsToCreate) + count($seatsToRestore);

        if ($totalProcessed === 0) {
            return ApiResponse::error('Tất cả các ghế đều đã tồn tại hoặc không hợp lệ.', 409);
        }

        // Convert merged array to collection
        $processedSeats = collect(array_merge(
            $seatsToCreate,
            array_map(function ($seat) { return $seat->toArray(); }, $seatsToRestore)
        ));
        return ApiResponse::success($processedSeats, 'Xử lý ghế hàng loạt thành công (' . count($seatsToRestore) . ' khôi phục, ' . count($seatsToCreate) . ' tạo mới)', 201);
    }

    // Xóa mềm nhiều ghế
    public function softDeleteMultiple(Request $request)
    {
        $request->validate([
            'room_id' => 'required|string',
            'prefix' => [
                'required',
                'string',
                'regex:/^[A-Z]{1}$/', // Chỉ cho phép 1 ký tự in hoa duy nhất
            ],
            'start_index' => 'required|integer|min:1',
            'end_index' => 'required|integer|gte:start_index',
        ], [
            'room_id.required' => 'Vui lòng nhập room_id.',
            'prefix.required' => 'Vui lòng nhập tiền tố tên ghế.',
            'prefix.regex' => 'Tiền tố chỉ được là 1 ký tự in hoa (A-Z).',
            'start_index.required' => 'Vui lòng nhập chỉ số bắt đầu.',
            'end_index.required' => 'Vui lòng nhập chỉ số kết thúc.',
            'end_index.gte' => 'Chỉ số kết thúc phải lớn hơn hoặc bằng bắt đầu.',
        ]);

        $roomId = $request->room_id;
        $prefix = $request->prefix;
        $start = $request->start_index;
        $end = $request->end_index;

        $seatsToDelete = [];

        for ($i = $start; $i <= $end; $i++) {
            $seatNumber = $prefix . $i;

            // Không cho phép định dạng như A01, AA5, a1,...
            if (!preg_match('/^[A-Z]{1}[1-9][0-9]*$/', $seatNumber)) {
                continue; // Bỏ qua ghế không đúng định dạng
            }

            // Tìm ghế trong phòng với seat_number
            $seat = Seat::where('room_id', $roomId)
                ->where('seat_number', $seatNumber)
                ->where('is_deleted', false)
                ->first();

            if ($seat) {
                $seatsToDelete[] = $seat;
            }
        }

        if (empty($seatsToDelete)) {
            return ApiResponse::error('Không tìm thấy ghế nào để xóa mềm.', 404);
        }

        // Thực hiện xóa mềm
        foreach ($seatsToDelete as $seat) {
            $seat->is_deleted = true;
            $seat->save();
        }

        return ApiResponse::success($seatsToDelete, 'Xóa mềm ' . count($seatsToDelete) . ' ghế thành công');
    }
}

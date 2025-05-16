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
                Rule::unique('seat')->where(function ($query) use ($request) {
                    return $query->where('room_id', $request->room_id)->where('is_deleted', false);
                }),
            ],
            'seat_type' => 'in:STANDARD,VIP,COUPLE',
        ], [
            'seat_number.unique' => 'Số ghế đã tồn tại trong phòng này.',
        ]);

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

    // Cập nhật thông tin ghế
    public function update(Request $request, $id)
    {
        $seat = Seat::find($id);

        if (!$seat || $seat->is_deleted) {
            return ApiResponse::error('Seat not found', 404);
        }

        $request->validate([
        'seat_number' => [
            'sometimes',
            'required',
            'string',
            'max:10',
            Rule::unique('seat')->where(function ($query) use ($request, $seat) {
                // Nếu room_id được gửi trong request thì lấy giá trị đó, nếu không thì lấy room_id hiện tại của ghế
                $roomId = $request->room_id ?? $seat->room_id;
                return $query->where('room_id', $roomId)->where('is_deleted', false);
            })->ignore($seat->seat_id, 'seat_id'),  // bỏ qua bản ghi hiện tại theo khóa chính seat_id
        ],
        'seat_type' => 'sometimes|in:STANDARD,VIP,COUPLE',
        'room_id' => 'sometimes|required|string',
    ], [
        'seat_number.unique' => 'Số ghế đã tồn tại trong phòng này.',
    ]);
        $seat->update($request->only(['seat_number', 'seat_type', 'room_id']));

        return ApiResponse::success($seat, 'Cập nhật ghế thành công');
    }

    // Xóa mềm
    public function softDelete($id)
    {
        $seat = Seat::find($id);
        if (!$seat || $seat->is_deleted) {
            return ApiResponse::error('Seat not found or already deleted', 404);
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
            return ApiResponse::error('Seat not found or not deleted', 404);
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


public function storeMultiple(Request $request)
{
    $request->validate([
        'room_id' => 'required|string',
        'prefix' => 'required|string|max:5',         // Ví dụ: "D"
        'start_index' => 'required|integer|min:1',
        'end_index' => 'required|integer|gte:start_index',
        'seat_type' => 'in:STANDARD,VIP,COUPLE',
    ], [
        'room_id.required' => 'Vui lòng nhập room_id.',
        'prefix.required' => 'Vui lòng nhập tiền tố tên ghế.',
        'start_index.required' => 'Vui lòng nhập chỉ số bắt đầu.',
        'end_index.required' => 'Vui lòng nhập chỉ số kết thúc.',
        'end_index.gte' => 'Chỉ số kết thúc phải lớn hơn hoặc bằng bắt đầu.',
    ]);

    $roomId = $request->room_id;
    $prefix = $request->prefix;
    $start = $request->start_index;
    $end = $request->end_index;
    $seatType = $request->seat_type ?? 'STANDARD';

    $seats = [];

    for ($i = $start; $i <= $end; $i++) {
        $seatNumber = $prefix . $i;

        // Kiểm tra trùng seat_number trong cùng phòng
        $exists = Seat::where('room_id', $roomId)
            ->where('seat_number', $seatNumber)
            ->where('is_deleted', false)
            ->exists();

        if ($exists) {
            continue; // Bỏ qua ghế trùng
        }

        $seats[] = [
            'seat_id' => (string) Str::uuid(),
            'room_id' => $roomId,
            'seat_number' => $seatNumber,
            'seat_type' => $seatType,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    if (count($seats) === 0) {
        return ApiResponse::error('Tất cả các ghế đều đã tồn tại hoặc không hợp lệ.', 409);
    }

    // Chèn hàng loạt
    Seat::insert($seats);

    return ApiResponse::success($seats, 'Tạo ghế hàng loạt thành công', 201);
}

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Response\ApiResponse;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    // Lấy danh sách tất cả phòng chưa bị xóa
    public function index()
    {
        $rooms = Room::where('is_deleted', false)->get();
        return ApiResponse::success($rooms, 'Danh sách phòng');
    }

    // Tạo mới một phòng
    public function store(Request $request)
    {
        $request->validate([
            'cinema_id' => 'required|string',
            'room_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('room')->where(function ($query) {
                    return $query->where('is_deleted', false);
                }),
            ],
            'capacity' => 'required|integer',
        ], [
            'cinema_id.required' => 'Vui lòng nhập cinema_id.',
            'cinema_id.string' => 'cinema_id phải là chuỗi.',
            'room_name.required' => 'Vui lòng nhập tên phòng.',
            'room_name.string' => 'Tên phòng phải là chuỗi.',
            'room_name.max' => 'Tên phòng không được vượt quá 100 ký tự.',
            'room_name.unique' => 'Tên phòng đã tồn tại.',
            'capacity.required' => 'Vui lòng nhập sức chứa phòng.',
            'capacity.integer' => 'Sức chứa phòng phải là số nguyên.',
        ]);

        $room = Room::create([
            'room_id' => (string) Str::uuid(),
            'cinema_id' => $request->cinema_id,
            'room_name' => $request->room_name,
            'capacity' => $request->capacity,
        ]);

        return ApiResponse::success($room, 'Tạo phòng thành công', 201);
    }

    // Lấy thông tin một phòng cụ thể
    public function show($id)
    {
        $room = Room::where('room_id', $id)->where('is_deleted', false)->first();

        if (!$room) {
            return ApiResponse::error('Room not found', 404);
        }

        return ApiResponse::success($room, 'Room found successfully');
    }

    // Cập nhật thông tin phòng
    public function update(Request $request, $id)
    {
        Log::info("PUT /api/rooms/{$id} - Bắt đầu cập nhật phòng");

        $room = Room::find($id);

        if (!$room || $room->is_deleted) {
            return ApiResponse::error('Room not found', 404);
        }

        // Validate dữ liệu cập nhật
        $request->validate([
            'cinema_id' => 'required|string',
            'room_name' => 'required|string|max:100|unique:room,room_name,' . $room->room_id . ',room_id',
            'capacity' => 'required|integer',
        ], [
            'cinema_id.required' => 'Vui lòng nhập cinema_id.',
            'cinema_id.string' => 'cinema_id phải là chuỗi.',
            'room_name.required' => 'Vui lòng nhập tên phòng.',
            'room_name.string' => 'Tên phòng phải là chuỗi.',
            'room_name.max' => 'Tên phòng không được vượt quá 100 ký tự.',
            'room_name.unique' => 'Tên phòng đã tồn tại.',
            'capacity.required' => 'Vui lòng nhập sức chứa phòng.',
            'capacity.integer' => 'Sức chứa phòng phải là số nguyên.',
        ]);

        // Cập nhật dữ liệu
        $room->update($request->only(['room_name', 'capacity', 'cinema_id']));

        return ApiResponse::success($room, 'Room updated successfully');
    }

    // Cập nhật dung tích phòng
    public function updateCapacity(Request $request, $id)
    {
        $room = Room::find($id);
        if (!$room || $room->is_deleted) {
            return ApiResponse::error('Room not found', 404);
        }

        $request->validate([
            'capacity' => 'required|integer',
        ], [
            'capacity.required' => 'Vui lòng nhập sức chứa phòng.',
            'capacity.integer' => 'Sức chứa phòng phải là số nguyên.',
        ]);

        $room->capacity = $request->capacity;
        $room->save();

        return ApiResponse::success($room, 'Room capacity updated successfully');
    }

    // Xóa mềm
    public function softDelete($id)
    {
        $room = Room::find($id);
        if (!$room || $room->is_deleted) {
            return ApiResponse::error('Phòng không tồn tại hoặc đã bị xóa!', 404);
        }

        $room->is_deleted = true;
        $room->save();

        return ApiResponse::success(null, 'Phòng đã được xóa!');
    }

    // Khôi phục lại phòng đã bị xóa mềm
    public function restore($id)
    {
        $room = Room::find($id);
        if (!$room || !$room->is_deleted) {
            return ApiResponse::error('Room not found or not deleted', 404);
        }

        $room->is_deleted = false;
        $room->save();

        return ApiResponse::success($room, 'Room restored');
    }

    // Xóa vĩnh viễn
    public function destroy($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return ApiResponse::error('Room not found', 404);
        }

        $room->delete();
        return ApiResponse::success(null, 'Room permanently deleted');
    }
}

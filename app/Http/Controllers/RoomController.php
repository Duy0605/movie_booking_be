<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Response\ApiResponse;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    // Lấy danh sách tất cả phòng chưa bị xóa, kèm thông tin cinema
    public function index(Request $request)
    {
        $query = Room::with([
            'cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ])
            ->where('is_deleted', false);

        // Filter by status if provided
        if ($request->has('status')) {
            $status = strtoupper($request->input('status'));
            if (in_array($status, ['AVAILABLE', 'UNAVAILABLE'])) {
                $query->where('status', $status);
            } else {
                return ApiResponse::error('Invalid status value. Must be AVAILABLE or UNAVAILABLE', 400);
            }
        }

        $rooms = $query->get();

        return ApiResponse::success($rooms, 'Danh sách phòng');
    }

    // Tạo mới một phòng
   public function store(Request $request)
    {
        try {
            // Xác thực dữ liệu
            $validated = $request->validate([
                'cinema_id' => 'required|string|exists:cinema,cinema_id',
                'room_name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'status' => 'sometimes|in:AVAILABLE,UNAVAILABLE',
            ], [
                'cinema_id.required' => 'Vui lòng nhập cinema_id.',
                'cinema_id.string' => 'cinema_id phải là chuỗi.',
                'cinema_id.exists' => 'Rạp không tồn tại.',
                'room_name.required' => 'Vui lòng nhập tên phòng.',
                'room_name.string' => 'Tên phòng phải là chuỗi.',
                'room_name.max' => 'Tên phòng không được vượt quá 100 ký tự.',
                'status.in' => 'Trạng thái phải là AVAILABLE hoặc UNAVAILABLE.',
            ]);

            // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
            \DB::beginTransaction();

            // Tạo phòng
            $room = Room::create([
                'room_id' => (string) Str::uuid(),
                'cinema_id' => $validated['cinema_id'],
                'room_name' => $validated['room_name'],
                'status' => $request->input('status', 'AVAILABLE'),
            ]);

            // Eager load cinema relationship
            $room->load([
                'cinema' => function ($query) {
                    $query->select('cinema_id', 'name')->where('is_deleted', false);
                }
            ]);

            \DB::commit();

            return ApiResponse::success($room, 'Tạo phòng thành công', 201);

        } catch (QueryException $e) {
            \DB::rollBack();
            // Ghi log chi tiết lỗi
            Log::error('Failed to create room', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'request_data' => $request->all(),
            ]);

            // Kiểm tra lỗi khóa ngoại
            if (str_contains($e->getMessage(), 'foreign key constraint fails')) {
                return ApiResponse::error('Cinema ID không tồn tại hoặc không hợp lệ.', 422);
            }

            // Trả về lỗi chi tiết
            return ApiResponse::error('Lỗi khi tạo phòng: ' . $e->getMessage(), 500);

        } catch (\Exception $e) {
            \DB::rollBack();
            // Ghi log lỗi chung
            Log::error('Unexpected error in store room', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return ApiResponse::error('Lỗi không xác định: ' . $e->getMessage(), 500);
        }
    }

    // Lấy thông tin một phòng cụ thể
    public function show($id)
    {
        $room = Room::with([
            'cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ])
            ->where('room_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$room) {
            return ApiResponse::error('Room not found', 404);
        }

        return ApiResponse::success($room, 'Room found successfully');
    }

    // Tìm kiếm phòng theo tên
    public function searchByRoomName(Request $request)
    {
        $keyword = $request->input('keyword');

        if (!$keyword) {
            return ApiResponse::error('Vui lòng nhập từ khóa tìm kiếm', 400);
        }

        $rooms = Room::with([
            'cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ])
            ->where('room_name', 'like', '%' . $keyword . '%')
            ->where('is_deleted', false)
            ->get();

        if ($rooms->isEmpty()) {
            return ApiResponse::success([], 'Không tìm thấy phòng nào phù hợp');
        }

        return ApiResponse::success($rooms, 'Danh sách phòng tìm thấy');
    }

    
    // Cập nhật thông tin phòng
    public function update(Request $request, $id)
    {
        $room = Room::find($id);

        if (!$room || $room->is_deleted) {
            return ApiResponse::error('Room not found', 404);
        }

        $request->validate([
            'room_name' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'status' => 'sometimes|in:AVAILABLE,UNAVAILABLE',
        ], [
            'room_name.required' => 'Vui lòng nhập tên phòng.',
            'room_name.string' => 'Tên phòng phải là chuỗi.',
            'room_name.max' => 'Tên phòng không được vượt quá 100 ký tự.',
            'room_name.unique' => 'Tên phòng đã tồn tại.',
            'status.in' => 'Trạng thái phải là AVAILABLE hoặc UNAVAILABLE.',
        ]);

        $room->update($request->only(['room_name', 'status']));

        // Eager load cinema relationship for the response
        $room->load([
            'cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ]);

        return ApiResponse::success($room, 'Cập nhật phòng thành công');
    }


    // Cập nhật dung tích phòng
    public function updateCapacity(Request $request, $id)
    {
        $room = Room::find($id);
        if (!$room || $room->is_deleted) {
            return ApiResponse::error('Không tìm thấy phòng', 404);
        }

        $request->validate([
            'capacity' => 'required|integer|min:1',
        ], [
            'capacity.required' => 'Vui lòng nhập sức chứa phòng.',
            'capacity.integer' => 'Sức chứa phòng phải là số nguyên.',
            'capacity.min' => 'Sức chứa phòng phải lớn hơn 0.',
        ]);

        $room->capacity = $request->capacity;
        $room->save();

        // Eager load cinema relationship for the response
        $room->load([
            'cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ]);

        return ApiResponse::success($room, 'Cập nhật dung tích phòng thành công');
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

    // Lấy danh sách các phòng đã bị xóa mềm
    public function getDeletedRooms()
    {
        $deletedRooms = Room::with([
            'cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ])
            ->where('is_deleted', true)
            ->get();

        if ($deletedRooms->isEmpty()) {
            return ApiResponse::success([], 'Không có phòng nào bị xóa mềm');
        }

        return ApiResponse::success($deletedRooms, 'Danh sách phòng đã bị xóa mềm');
    }

    // Khôi phục lại phòng đã bị xóa mềm
    public function restore($id)
    {
        $room = Room::find($id);
        if (!$room || !$room->is_deleted) {
            return ApiResponse::error('Phòng không tồn tại hoặc đã bị xóa', 404);
        }

        $room->is_deleted = false;
        $room->save();

        // Eager load cinema relationship for the response
        $room->load([
            'cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ]);

        return ApiResponse::success($room, 'Phòng đã được khôi phục thành công');
    }

    // Xóa vĩnh viễn
    public function destroy($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return ApiResponse::error('Phòng không tồn tại hoặc đã bị xóa', 404);
        }

        $room->delete();
        return ApiResponse::success(null, 'Phòng đã được xóa vĩnh viễn');
    }

    // Lấy danh sách phòng theo cinema_id
    public function getRoomsByCinema(Request $request, $cinema_id)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Check if cinema exists and is not deleted
        $cinema = Cinema::where('cinema_id', $cinema_id)
            ->where('is_deleted', false)
            ->first();

        if (!$cinema) {
            return ApiResponse::error('Rạp không tồn tại hoặc đã bị xóa', 404);
        }

        $query = Room::with([
            'cinema' => function ($query) {
                $query->select('cinema_id', 'name')->where('is_deleted', false);
            }
        ])
            ->where('cinema_id', $cinema_id)
            ->where('is_deleted', false);

        // Filter by status if provided
        if ($request->has('status')) {
            $status = strtoupper($request->input('status'));
            if (in_array($status, ['AVAILABLE', 'UNAVAILABLE'])) {
                $query->where('status', $status);
            } else {
                return ApiResponse::error('Invalid status value. Must be AVAILABLE or UNAVAILABLE', 400);
            }
        }

        $rooms = $query->paginate($perPage, ['*'], 'page', $page);

        return ApiResponse::success($rooms, 'Danh sách phòng theo rạp');
    }
}
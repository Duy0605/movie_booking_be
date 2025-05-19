<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Response\ApiResponse;

class CinemaController extends Controller
{
    // Lấy danh sách rạp
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Số lượng bản ghi mỗi trang (mặc định 10)
        $page = $request->input('page', 1);         // Trang hiện tại (mặc định 1)

        $cinemas = Cinema::where('is_deleted', false)
            ->paginate($perPage, ['*'], 'page', $page);

        return ApiResponse::success($cinemas, 'Danh sách rạp chiếu phim');
    }


    // Tạo mới rạp
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        $name = strtolower(trim($request->name));
        $address = strtolower(trim($request->address));

        $exists = Cinema::where(function ($query) use ($request) {
            $query->where('name', $request->name)
                ->orWhere('address', $request->address);
        })
            ->where('is_deleted', false)
            ->exists();

        if ($exists) {
            return ApiResponse::error('Tên hoặc địa chỉ đã tồn tại', 409);
        }

        $cinema = Cinema::create([
            'cinema_id' => Str::uuid(),
            'name' => $request->name,
            'address' => $request->address,
        ]);

        return ApiResponse::success($cinema, 'Tạo rạp chiếu phim thành công', 201);
    }


    // Lấy thông tin 1 rạp
    public function show($id)
    {
        $cinema = Cinema::where('cinema_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$cinema) {
            return ApiResponse::error('Không tìm thấy rạp chiếu phim', 404);
        }

        return ApiResponse::success($cinema, 'Thông tin rạp chiếu phim');
    }

    // Cập nhật thông tin rạp
    public function update(Request $request, $id)
    {
        // Validate bắt buộc tên và địa chỉ không để trống
        $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
        ], [
            'name.required' => 'Tên rạp không được để trống',
            'address.required' => 'Địa chỉ rạp không được để trống',
        ]);

        $cinema = Cinema::where('cinema_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$cinema) {
            return ApiResponse::error('Không tìm thấy rạp để cập nhật', 404);
        }

        // Kiểm tra trùng tên hoặc trùng địa chỉ với các rạp khác (không tính rạp hiện tại)
        $exists = Cinema::where(function ($query) use ($request) {
            $query->where('name', $request->name)
                ->orWhere('address', $request->address);
        })
            ->where('is_deleted', false)
            ->exists();

        if ($exists) {
            return ApiResponse::error('Tên hoặc địa chỉ rạp đã tồn tại', 400);
        }

        $cinema->update([
            'name' => $request->name,
            'address' => $request->address,
        ]);

        return ApiResponse::success($cinema, 'Cập nhật rạp chiếu phim thành công');
    }


    // Xoá mềm rạp
    public function destroy($id)
    {
        $cinema = Cinema::where('cinema_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$cinema) {
            return ApiResponse::error('Không tìm thấy rạp để xoá', 404);
        }

        $cinema->update(['is_deleted' => true]);
        return ApiResponse::success(null, 'Xoá rạp chiếu phim thành công');
    }
}

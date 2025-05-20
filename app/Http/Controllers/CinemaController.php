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
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $cinemas = Cinema::where('is_deleted', false)
            ->paginate($perPage, ['*'], 'page', $page);

        return ApiResponse::success($cinemas, 'Danh sách rạp chiếu phim');
    }

    // Lấy danh sách rạp đã xóa
    public function getDeleted(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $cinemas = Cinema::where('is_deleted', true)
            ->paginate($perPage, ['*'], 'page', $page);

        return ApiResponse::success($cinemas, 'Danh sách rạp chiếu phim đã xóa');
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
        $request->validate([
            'name' => 'sometimes|string',
            'address' => 'sometimes|string',
        ], [
            'name.string' => 'Tên rạp phải là chuỗi ký tự',
            'address.string' => 'Địa chỉ rạp phải là chuỗi ký tự',
        ]);

        $cinema = Cinema::where('cinema_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$cinema) {
            return ApiResponse::error('Không tìm thấy rạp để cập nhật', 404);
        }

        $updateData = [
            'name' => $request->has('name') ? $request->name : $cinema->name,
            'address' => $request->has('address') ? $request->address : $cinema->address,
        ];

        $exists = Cinema::where(function ($query) use ($updateData) {
            $query->where('name', $updateData['name'])
                ->orWhere('address', $updateData['address']);
        })
            ->where('is_deleted', false)
            ->where('cinema_id', '!=', $id)
            ->exists();

        if ($exists) {
            return ApiResponse::error('Tên hoặc địa chỉ rạp đã tồn tại', 400);
        }

        if ($updateData['name'] !== $cinema->name || $updateData['address'] !== $cinema->address) {
            $cinema->update($updateData);
        }

        return ApiResponse::success($cinema, 'Cập nhật rạp chiếu phim thành công');
    }

    // Xóa mềm rạp
    public function destroy($id)
    {
        $cinema = Cinema::where('cinema_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$cinema) {
            return ApiResponse::error('Không tìm thấy rạp để xóa', 404);
        }

        $cinema->update(['is_deleted' => true]);
        return ApiResponse::success(null, 'Xóa rạp chiếu phim thành công');
    }

    // Khôi phục rạp đã xóa mềm
    public function restore($id)
    {
        $cinema = Cinema::where('cinema_id', $id)
            ->where('is_deleted', true)
            ->first();

        if (!$cinema) {
            return ApiResponse::error('Không tìm thấy rạp để khôi phục hoặc rạp chưa bị xóa', 404);
        }

        $cinema->update(['is_deleted' => false]);
        return ApiResponse::success($cinema, 'Khôi phục rạp chiếu phim thành công');
    }
}
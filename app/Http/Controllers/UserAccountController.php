<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Response\ApiResponse;

class UserAccountController extends Controller
{
    // Lấy danh sách người dùng (chưa bị xoá)
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // số bản ghi mỗi trang
        $page = $request->input('page', 1);         // trang hiện tại

        $users = UserAccount::where('is_deleted', false)
            ->where('role', 'use') // chỉ lấy người dùng
            ->paginate($perPage, ['*'], 'page', $page);

        return ApiResponse::success($users, 'Danh sách người dùng');
    }


    // Lấy thông tin một người dùng theo ID
    public function show($id)
    {
        $user = UserAccount::where('user_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$user) {
            return ApiResponse::error('Không tìm thấy người dùng', 404);
        }

        return ApiResponse::success($user, 'Thông tin người dùng');
    }

    // Tạo mới người dùng
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:user_account,username',
            'email' => 'required|email|unique:user_account,email',
            'password' => 'required|string|min:6',
            'full_name' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'phone' => 'required|string|max:15|unique:user_account,phone',
            'profile_picture_url' => 'url',
            'role' => 'string|max:20', // nếu có sử dụng phân quyền
        ]);

        $user = UserAccount::create([
            'user_id' => (string) Str::uuid(),
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'full_name' => $request->full_name,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'profile_picture_url' => $request->profile_picture_url,
            'role' => $request->role ?? 'user',
            'is_deleted' => false,
        ]);

        return ApiResponse::success($user, 'Tạo người dùng thành công', 201);
    }


    // Cập nhật thông tin người dùng
    public function update(Request $request, $id)
    {
        $user = UserAccount::where('user_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$user) {
            return ApiResponse::error('Không tìm thấy người dùng để cập nhật', 404);
        }

        $request->validate([
            'email' => 'nullable|email|unique:user_account,email,' . $user->user_id . ',user_id',
            'phone' => 'nullable|string|max:15|unique:user_account,phone,' . $user->user_id . ',user_id',
            'full_name' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'profile_picture_url' => 'url',
        ]);

        $user->update($request->only([
            'email',
            'full_name',
            'dob',
            'phone',
            'profile_picture_url',
        ]));

        return ApiResponse::success($user, 'Cập nhật người dùng thành công');
    }

    // Xoá mềm người dùng
    public function destroy($id)
    {
        $user = UserAccount::where('user_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$user) {
            return ApiResponse::error('Không tìm thấy người dùng để xoá', 404);
        }

        $user->update(['is_deleted' => true]);

        return ApiResponse::success(null, 'Xoá người dùng thành công');
    }

    // Khôi phục người dùng đã bị xóa mềm
    public function restore($id)
    {
        $user = UserAccount::where('user_id', $id)
            ->where('is_deleted', true)
            ->first();

        if (!$user) {
            return ApiResponse::error('Không tìm thấy người dùng đã bị xóa hoặc chưa bị xóa', 404);
        }

        $user->update(['is_deleted' => false]);

        return ApiResponse::success($user, 'Khôi phục người dùng thành công');
    }

    // Xóa vĩnh viễn người dùng
    public function forceDelete($id)
    {
        $user = UserAccount::where('user_id', $id)->first();

        if (!$user) {
            return ApiResponse::error('Không tìm thấy người dùng để xóa vĩnh viễn', 404);
        }

        $user->delete(); // xóa hẳn khỏi DB

        return ApiResponse::success(null, 'Xóa người dùng vĩnh viễn thành công');
    }

}

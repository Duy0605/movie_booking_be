<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Response\ApiResponse;

class UserAccountController extends Controller
{
    // Lấy danh sách người dùng
    public function index()
    {
        $users = UserAccount::where('is_deleted', false)->get();
        return ApiResponse::success($users, 'Danh sách người dùng');
    }

    // Lấy thông tin 1 người dùng theo ID
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
            'full_name' => 'required|string|max:100', 
            'dob' => 'nullable|date', 
            'phone' => 'required|string|max:15|unique:user_account,phone',
        ]);

        $user = UserAccount::create([
            'user_id' => Str::uuid(),
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'full_name' => $request->full_name,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'profile_picture_url' => $request->profile_picture_url ?? null,
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
        ]);

        $user->update([
            'email' => $request->email ?? $user->email,
            'full_name' => $request->full_name ?? $user->full_name,
            'dob' => $request->dob ?? $user->dob,
            'phone' => $request->phone ?? $user->phone,
            'profile_picture_url' => $request->profile_picture_url ?? $user->profile_picture_url,
        ]);

        return ApiResponse::success($user, 'Cập nhật người dùng thành công');
    }

    // Xóa mềm người dùng
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
}

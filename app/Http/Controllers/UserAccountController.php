<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Response\ApiResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;

class UserAccountController extends Controller
{
    // Lấy danh sách người dùng (chưa bị xoá)
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $users = UserAccount::where('is_deleted', false)
            ->where('role', 'USER')
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
            'role' => 'string|max:20',
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

    // Tìm kiếm người dùng theo tên hoặc số điện thoại
    public function search(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
        ]);

        $keyword = $request->input('keyword');
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $users = UserAccount::where('is_deleted', false)
            ->where('role', 'USER')
            ->where(function ($query) use ($keyword) {
                $query->where('username', 'LIKE', "%$keyword%")
                    ->orWhere('phone', 'LIKE', "%$keyword%");
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return ApiResponse::success($users, 'Kết quả tìm kiếm người dùng');
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

        $user->delete();

        return ApiResponse::success(null, 'Xóa người dùng vĩnh viễn thành công');
    }

    // Đổi mật khẩu người dùng
    public function changePassword(Request $request, $id)
    {
        $user = UserAccount::where('user_id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$user) {
            return ApiResponse::error('Không tìm thấy người dùng', 404);
        }

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->old_password, $user->password)) {
            return ApiResponse::error('Mật khẩu cũ không đúng', 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return ApiResponse::success(null, 'Đổi mật khẩu thành công');
    }

    // Quên mật khẩu
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = UserAccount::where('email', $request->email)
            ->where('is_deleted', false)
            ->first();

        if (!$user) {
            return ApiResponse::error('Không tìm thấy người dùng với email này', 404);
        }

        $newPassword = Str::random(12);

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        Mail::to($user->email)->send(new PasswordResetMail(
            $user->full_name,
            $newPassword
        ));

        return ApiResponse::success(null, 'Mật khẩu đã được đặt lại và email thông báo đã được gửi');
    }
}
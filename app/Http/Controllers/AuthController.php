<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Response\ApiResponse;

class AuthController extends Controller
{
    // Đăng ký tài khoản
    public function register(Request $request)
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

        return ApiResponse::success($user, 'Đăng ký tài khoản thành công', 201);
    }

    // Đăng nhập
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = UserAccount::where('email', $request->email)
            ->where('is_deleted', false)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        // Tạo api_token thủ công
        $token = Str::random(60);
        $user->api_token = hash('sha256', $token); // Băm để bảo mật hơn
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'token' => $token, // Trả token gốc, không băm
            'user' => [
                'user_id' => $user->user_id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'dob' => $user->dob,
                'phone' => $user->phone,
                'profile_picture_url' => $user->profile_picture_url
            ]
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Response\ApiResponse;

class AuthController extends Controller
{
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

        try {
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

            $token = JWTAuth::fromUser($user);

            return ApiResponse::success([
                'user' => [
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'dob' => $user->dob,
                    'phone' => $user->phone,
                    'profile_picture_url' => $user->profile_picture_url
                ],
                'access_token' => $token,
                'refresh_token' => $token // Same token for simplicity
            ], 'Đăng ký tài khoản thành công', 201);
        } catch (JWTException $e) {
            return ApiResponse::error('Không thể tạo token: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            return ApiResponse::error('Đăng ký thất bại: ' . $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return ApiResponse::error('Email hoặc mật khẩu không đúng', 401);
            }

            $user = Auth::user();
            $refreshToken = JWTAuth::fromUser($user); // Same token for refresh

            return ApiResponse::success([
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'user' => [
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'dob' => $user->dob,
                    'phone' => $user->phone,
                    'profile_picture_url' => $user->profile_picture_url
                ]
            ], 'Đăng nhập thành công');
        } catch (JWTException $e) {
            return ApiResponse::error('Không thể tạo token: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            return ApiResponse::error('Đăng nhập thất bại: ' . $e->getMessage(), 500);
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return ApiResponse::success(null, 'Đăng xuất thành công');
        } catch (JWTException $e) {
            return ApiResponse::error('Đăng xuất thất bại: ' . $e->getMessage(), 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return ApiResponse::success([
                'access_token' => $newToken,
                'refresh_token' => $newToken // Same token for simplicity
            ], 'Làm mới token thành công');
        } catch (JWTException $e) {
            return ApiResponse::error('Không thể làm mới token: ' . $e->getMessage(), 401);
        }
    }
}
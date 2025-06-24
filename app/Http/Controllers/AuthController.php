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

            // Tạo access token với TTL ngắn (60 phút)
            $accessToken = JWTAuth::customClaims(['type' => 'access'])->fromUser($user);
            
            // Tạo refresh token với TTL dài hơn (2 tuần)
            $refreshToken = JWTAuth::customClaims([
                'type' => 'refresh',
                'exp' => now()->addDays(14)->timestamp // 14 ngày
            ])->fromUser($user);

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
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken
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
            // Không sử dụng JWTAuth::attempt() vì nó tạo token mặc định
            if (!Auth::attempt($credentials)) {
                return ApiResponse::error('Email hoặc mật khẩu không đúng', 401);
            }

            $user = Auth::user();
            
            // Tạo access token với TTL ngắn
            $accessToken = JWTAuth::customClaims(['type' => 'access'])->fromUser($user);
            
            // Tạo refresh token với TTL dài hơn
            $refreshToken = JWTAuth::customClaims([
                'type' => 'refresh',
                'exp' => now()->addDays(14)->timestamp // 14 ngày
            ])->fromUser($user);

            return ApiResponse::success([
                'access_token' => $accessToken,
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
            // Lấy refresh token từ request body thay vì header
            $refreshToken = $request->input('refresh_token');
            
            if (!$refreshToken) {
                return ApiResponse::error('Refresh token không được cung cấp', 401);
            }

            // Set token để xử lý
            JWTAuth::setToken($refreshToken);
            
            // Xác thực refresh token
            try {
                $payload = JWTAuth::getPayload();
            } catch (TokenExpiredException $e) {
                return ApiResponse::error('Refresh token đã hết hạn, vui lòng đăng nhập lại', 401);
            } catch (TokenInvalidException $e) {
                return ApiResponse::error('Refresh token không hợp lệ', 401);
            }
            
            // Kiểm tra xem đây có phải là refresh token không
            if ($payload->get('type') !== 'refresh') {
                return ApiResponse::error('Token không phải là refresh token', 401);
            }

            // Lấy user từ token
            $user = JWTAuth::toUser($refreshToken);
            
            if (!$user) {
                return ApiResponse::error('Không tìm thấy người dùng', 401);
            }
            
            // Tạo access token mới
            $newAccessToken = JWTAuth::customClaims(['type' => 'access'])->fromUser($user);
            
            // Tạo refresh token mới
            $newRefreshToken = JWTAuth::customClaims([
                'type' => 'refresh',
                'exp' => now()->addDays(14)->timestamp
            ])->fromUser($user);
            
            // Invalidate refresh token cũ
            JWTAuth::invalidate($refreshToken);

            return ApiResponse::success([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'user' => [
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'dob' => $user->dob,
                    'phone' => $user->phone,
                    'profile_picture_url' => $user->profile_picture_url
                ]
            ], 'Làm mới token thành công');
            
        } catch (JWTException $e) {
            return ApiResponse::error('Không thể làm mới token: ' . $e->getMessage(), 401);
        } catch (\Exception $e) {
            return ApiResponse::error('Làm mới token thất bại: ' . $e->getMessage(), 401);
        }
    }

    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return ApiResponse::error('User không tìm thấy', 404);
            }

            return ApiResponse::success([
                'user' => [
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'dob' => $user->dob,
                    'phone' => $user->phone,
                    'profile_picture_url' => $user->profile_picture_url
                ]
            ], 'Lấy thông tin user thành công');
            
        } catch (JWTException $e) {
            return ApiResponse::error('Token không hợp lệ: ' . $e->getMessage(), 401);
        }
    }
}
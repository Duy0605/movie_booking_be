<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Http\Request;

class JWTAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Parse và authenticate token
            $user = JWTAuth::parseToken()->authenticate();
            
            // Lấy payload để kiểm tra loại token
            $payload = JWTAuth::getPayload();
            
            // Chỉ cho phép access token truy cập các API bảo vệ
            if ($payload->get('type') === 'refresh') {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể sử dụng refresh token để truy cập API này',
                    'error' => 'invalid_token_type'
                ], 401);
            }
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy người dùng',
                    'error' => 'user_not_found'
                ], 401);
            }
            
            return $next($request);
            
        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token không hợp lệ',
                    'error' => 'token_invalid'
                ], 401);
            }
            if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token đã hết hạn, vui lòng làm mới token',
                    'error' => 'token_expired'
                ], 401);
            }
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy token xác thức',
                'error' => 'token_not_found'
            ], 401);
        }
    }
}
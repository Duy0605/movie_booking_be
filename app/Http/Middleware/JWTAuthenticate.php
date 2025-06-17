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
            JWTAuth::parseToken()->authenticate();
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
                    'message' => 'Token đã hết hạn',
                    'error' => 'token_expired'
                ], 401);
            }
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy token xác thực',
                'error' => 'token_not_found'
            ], 401);
        }
    }
} 
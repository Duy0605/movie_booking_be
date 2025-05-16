<?php

namespace App\Response;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Trả về response thành công
     */
    public static function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ], $code);
    }

    /**
     * Trả về response lỗi
     */
    public static function error(string $message = 'Error', int $code = 400, $data = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ], $code);
    }
}

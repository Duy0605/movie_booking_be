<?php
namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    // GET /api/setting
    public function show()
    {
        $setting = Setting::where('is_deleted', 0)->first();

        if (!$setting) {
            return response()->json([
                'code' => 404,
                'message' => 'Không tìm thấy cài đặt',
                'data' => null
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Lấy cài đặt thành công',
            'data' => $setting
        ]);
    }

    // PUT /api/setting
    public function update(Request $request)
    {
        $setting = Setting::where('is_deleted', 0)->first();

        if (!$setting) {
            return response()->json([
                'code' => 404,
                'message' => 'Không tìm thấy cài đặt để cập nhật',
                'data' => null
            ], 404);
        }

        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|url',
            'vip' => 'required|numeric|min:0|max:100',
            'couple' => 'required|numeric|min:100|max:200',
            'banner' => 'required|array',
            'banner.*' => 'string|url|regex:/^https:\/\/res\.cloudinary\.com/', // Ensure each URL is a valid Cloudinary URL
        ]);

        // Update the setting with the validated data
        $setting->update($validated);

        return response()->json([
            'code' => 200,
            'message' => 'Cập nhật cài đặt thành công',
            'data' => $setting
        ]);
    }
}
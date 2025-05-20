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

        $setting->update($request->only(['name', 'vip', 'couple', 'banner']));

        return response()->json([
            'code' => 200,
            'message' => 'Cập nhật cài đặt thành công',
            'data' => $setting
        ]);
    }
}

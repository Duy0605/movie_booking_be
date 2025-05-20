<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use App\Models\Coupon;
use App\Models\UserAccount;
use App\Mail\CouponMail;
use App\Response\ApiResponse;

class CouponController extends Controller
{
    // Lấy danh sách coupon
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Số lượng mỗi trang, mặc định là 10

        $coupons = Coupon::paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách coupon thành công',
            'data' => $coupons
        ]);
    }


    // Tạo coupon mới
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupon,code',
            'discount' => 'required|numeric|min:0|max:100',
            'expiry_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $coupon = Coupon::create([
            'coupon_id' => Str::uuid()->toString(),
            'code' => $request->code,
            'discount' => $request->discount,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active ?? true,
        ]);

        // Lấy danh sách người dùng
        $users = UserAccount::all();

        foreach ($users as $user) {
            Mail::to($user->email)->queue(new CouponMail(
                $user->full_name,
                $coupon->code,
                $coupon->expiry_date->format('d/m/Y'),
                $coupon->discount
            ));
        }

        return response()->json(['code' => 201, 'message' => 'Tạo coupon thành công và đã gửi mail', 'data' => $coupon]);
    }

    // Xem chi tiết coupon
    public function show($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['code' => 404, 'message' => 'Coupon không tồn tại']);
        }
        return response()->json(['code' => 200, 'message' => 'Success', 'data' => $coupon]);
    }

    // Cập nhật coupon
    public function update(Request $request, $id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['code' => 404, 'message' => 'Coupon không tồn tại']);
        }

        $request->validate([
            'code' => 'string|unique:coupon,code,' . $id . ',coupon_id',
            'discount' => 'numeric|min:0|max:100',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ]);

        $coupon->code = $request->code ?? $coupon->code;
        $coupon->discount = $request->discount ?? $coupon->discount;
        $coupon->expiry_date = $request->expiry_date ?? $coupon->expiry_date;
        $coupon->is_active = $request->is_active ?? $coupon->is_active;

        $coupon->save();

        return response()->json(['code' => 200, 'message' => 'Cập nhật coupon thành công', 'data' => $coupon]);
    }

    // Xóa mềm coupon
    public function softDelete($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['code' => 404, 'message' => 'Coupon không tồn tại']);
        }

        $coupon->is_active = false;
        $coupon->save();

        return response()->json(['code' => 200, 'message' => 'Đã vô hiệu hóa coupon']);
    }

    //Khôi phục coupon
    public function restore($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return ApiResponse::error('Coupon không tồn tại', 404);
        }

        if ($coupon->is_active) {
            return ApiResponse::error('Coupon chưa bị vô hiệu hóa, không thể khôi phục', 400);
        }

        $coupon->is_active = true;
        $coupon->save();

        return ApiResponse::success($coupon, 'Khôi phục coupon thành công');
    }

    // Xóa cứng coupon
    public function forceDelete($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['code' => 404, 'message' => 'Coupon không tồn tại']);
        }

        $coupon->delete();

        return response()->json(['code' => 200, 'message' => 'Xóa coupon thành công']);
    }
}

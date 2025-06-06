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
            'description' => 'nullable|string',
            'discount' => 'required|numeric|min:0|max:100',
            'expiry_date' => 'required|date|after:today',
            'is_active' => 'boolean',
            'is_used' => 'integer|min:0', // Validate is_used as an integer
            'quantity' => 'integer|min:1', // Validate quantity
        ]);

        $coupon = Coupon::create([
            'coupon_id' => Str::uuid()->toString(),
            'code' => $request->code,
            'description' => $request->description,
            'discount' => $request->discount,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active ?? true,
            'is_used' => $request->is_used ?? 0, // Default to 0 if not provided
            'quantity' => $request->quantity ?? 1, // Default to 1 if not provided
        ]);

        // Lấy danh sách người dùng
        $users = UserAccount::all();

        foreach ($users as $user) {
            Mail::to($user->email)->queue(new CouponMail(
                $user->full_name,
                $coupon->code,
                $coupon->expiry_date->format('d/m/Y'),
                $coupon->discount,
                $coupon->description
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
            'description' => 'nullable|string',
            'discount' => 'numeric|min:0|max:100',
            'expiry_date' => 'date|after:today',
            'is_active' => 'boolean',
            'is_used' => 'integer|min:0', // Validate is_used as an integer
            'quantity' => 'integer|min:1', // Validate quantity
        ]);

        $coupon->code = $request->code ?? $coupon->code;
        $coupon->description = $request->description ?? $coupon->description;
        $coupon->discount = $request->discount ?? $coupon->discount;
        $coupon->expiry_date = $request->expiry_date ?? $coupon->expiry_date;
        $coupon->is_active = $request->is_active ?? $coupon->is_active;
        $coupon->is_used = $request->is_used ?? $coupon->is_used; // Update is_used
        $coupon->quantity = $request->quantity ?? $coupon->quantity; // Update quantity

        // Ensure is_used does not exceed quantity
        if ($coupon->is_used > $coupon->quantity) {
            return response()->json([
                'code' => 400,
                'message' => 'Số lượng sử dụng (is_used) không thể lớn hơn số lượng tối đa (quantity)',
            ], 400);
        }

        $coupon->save();

        return response()->json(['code' => 200, 'message' => 'Cập nhật coupon thành công', 'data' => $coupon]);
    }

    // Lấy danh sách coupon đã bị xóa mềm (is_active = false)
    public function getDeletedCoupons(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $coupons = Coupon::where('is_active', false)
            ->paginate($perPage, ['*'], 'page', $page);

        if ($coupons->isEmpty()) {
            return response()->json([
                'code' => 404,
                'message' => 'Không có coupon nào bị vô hiệu hóa.',
            ]);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Danh sách coupon đã bị xóa mềm',
            'data' => $coupons,
        ]);
    }

    // Tìm kiếm coupon theo code
    public function searchCouponByCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'per_page' => 'sometimes|integer|min:1',
            'page' => 'sometimes|integer|min:1',
        ], [
            'code.required' => 'Vui lòng nhập mã coupon để tìm kiếm.',
        ]);

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $searchCode = $request->input('code');

        $coupons = Coupon::where('code', 'like', '%' . $searchCode . '%')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($coupons->isEmpty()) {
            return response()->json([
                'code' => 404,
                'message' => 'Không tìm thấy coupon với mã đã nhập.',
            ]);
        }
        return response()->json([
            'code' => 200,
            'message' => 'Tìm kiếm coupon thành công',
            'data' => $coupons
        ]);
    }

    // Tìm kiếm coupon theo code (exact match)
    public function searchByExactCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = $request->input('code');
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'code' => 404,
                'message' => 'Không tìm thấy coupon với mã chính xác này',
                'data' => null
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Danh sách coupon theo mã tìm kiếm',
            'data' => $coupon,
        ]);
    }

    public function updateUsage(Request $request, $coupon_id)
    {
        // Validate the request
        $request->validate([
            'action' => 'required|string|in:increment,decrement',
        ]);

        // Find the coupon
        $coupon = Coupon::find($coupon_id);
        if (!$coupon) {
            return ApiResponse::error('Coupon not found', 404);
        }

        // Check if the coupon is active
        if (!$coupon->is_active) {
            return ApiResponse::error('Cannot update usage: Coupon is not active', 400);
        }

        $action = $request->input('action');

        if ($action === 'increment') {
            // Check if usage limit is reached
            if ($coupon->is_used >= $coupon->quantity) {
                return ApiResponse::error('Cannot increment usage: Coupon has reached its usage limit', 400);
            }
            $coupon->is_used += 1;
        } elseif ($action === 'decrement') {
            // Check if usage can be decremented
            if ($coupon->is_used <= 0) {
                return ApiResponse::error('Cannot decrement usage: Coupon usage is already 0', 400);
            }
            $coupon->is_used -= 1;
        }

        // Save the updated coupon
        $coupon->save();

        return ApiResponse::success($coupon, 'Coupon usage updated successfully');
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

    // Khôi phục coupon
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
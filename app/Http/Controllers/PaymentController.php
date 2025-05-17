<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // Lấy danh sách payment
    public function index()
    {
        $payments = Payment::with('booking')->get();
        return response()->json(['code' => 200, 'message' => 'Success', 'data' => $payments]);
    }

    // Tạo payment mới
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|string|exists:booking,booking_id',
            'amount' => 'required|numeric|min:0',
            'payment_status' => 'in:PENDING,COMPLETED,FAILED',
            'barcode' => 'string',
            'is_use' => 'boolean',
        ]);

        // Kiểm tra booking đã có payment trạng thái COMPLETED chưa
        $existsCompleted = Payment::where('booking_id', $request->booking_id)
            ->where('payment_status', 'COMPLETED')
            ->exists();

        if ($existsCompleted) {
            return response()->json([
                'code' => 400,
                'message' => 'Booking này đã có payment trạng thái COMPLETED, không thể tạo payment mới.'
            ], 400);
        }

        $payment = Payment::create([
            'payment_id' => Str::uuid()->toString(),
            'booking_id' => $request->booking_id,
            'amount' => $request->amount,
            'payment_status' => $request->payment_status ?? 'PENDING',
            'barcode' => $request->barcode,
            'is_use' => $request->is_use ?? false
        ]);

        return response()->json(['code' => 201, 'message' => 'Tạo payment thành công', 'data' => $payment]);
    }


    // Xem chi tiết payment theo id
    public function show($id)
    {
        $payment = Payment::with('booking')->find($id);
        if (!$payment) {
            return response()->json(['code' => 404, 'message' => 'Payment không tồn tại']);
        }
        return response()->json(['code' => 200, 'message' => 'Success', 'data' => $payment]);
    }

    // Cập nhật payment (ví dụ cập nhật trạng thái)
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['code' => 404, 'message' => 'Payment không tồn tại']);
        }

        $request->validate([
            'payment_status' => 'in:PENDING,COMPLETED,FAILED',
            'amount' => 'numeric|min:0',
            'barcode' => 'string',
            'is_use' => 'boolean'
        ]);

        $payment->payment_status = $request->payment_status ?? $payment->payment_status;
        $payment->amount = $request->amount ?? $payment->amount;
        $payment->barcode = $request->barcode ?? $payment->barcode;
        $payment->is_use = $request->is_use ?? $payment->is_use;

        $payment->save();

        return response()->json(['code' => 200, 'message' => 'Cập nhật payment thành công', 'data' => $payment]);
    }

    // Xóa payment (soft delete hoặc xóa cứng tùy)
    public function destroy($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['code' => 404, 'message' => 'Payment không tồn tại']);
        }

        $payment->delete();

        return response()->json(['code' => 200, 'message' => 'Xóa payment thành công']);
    }


    public function markCompleted($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['code' => 404, 'message' => 'Payment không tồn tại']);
        }

        // Kiểm tra đã có payment COMPLETED khác của booking chưa
        $existsCompleted = Payment::where('booking_id', $payment->booking_id)
            ->where('payment_status', 'COMPLETED')
            ->where('payment_id', '!=', $payment->payment_id)
            ->exists();

        if ($existsCompleted) {
            return response()->json([
                'code' => 400,
                'message' => 'Booking này đã có payment trạng thái COMPLETED, không thể cập nhật sang COMPLETED nữa.'
            ], 400);
        }

        // // Lấy thông tin booking để kiểm tra số tiền cần thanh toán
        // $booking = $payment->booking;
        // if (!$booking) {
        //     return response()->json(['code' => 400, 'message' => 'Booking không hợp lệ']);
        // }

        // // Giả sử booking có trường total_amount là số tiền cần thanh toán
        // if ($payment->amount < $booking->total_price) {
        //     return response()->json([
        //         'code' => 400,
        //         'message' => 'Số tiền thanh toán chưa đủ để chuyển trạng thái sang COMPLETED.'
        //     ], 400);
        // }

        // Nếu đủ tiền, cho chuyển sang COMPLETED
        $payment->payment_status = 'COMPLETED';
        $payment->save();

        return response()->json(['code' => 200, 'message' => 'Cập nhật trạng thái sang COMPLETED thành công', 'data' => $payment]);
    }
}

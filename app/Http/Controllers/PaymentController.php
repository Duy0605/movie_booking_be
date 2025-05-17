<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::where('is_deleted', false)->get();

        return response()->json([
            'code' => 200,
            'message' => 'Lấy danh sách thanh toán thành công',
            'data' => $payments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|string|exists:booking,booking_id',
            'payment_method' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'status' => 'required|string|in:pending,completed,failed',
        ]);

        $payment = Payment::create([
            'payment_id' => Str::uuid()->toString(),
            'booking_id' => $request->booking_id,
            'payment_method' => $request->payment_method,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'status' => $request->status,
            'is_deleted' => false,
        ]);

        return response()->json([
            'code' => 201,
            'message' => 'Tạo thanh toán thành công',
            'data' => $payment
        ]);
    }

    public function show($id)
    {
        try {
            $payment = Payment::where('payment_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            return response()->json([
                'code' => 200,
                'message' => 'Lấy thông tin thanh toán thành công',
                'data' => $payment
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Thanh toán không tồn tại'
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $payment = Payment::where('payment_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            $request->validate([
                'booking_id' => 'sometimes|required|string|exists:booking,booking_id',
                'payment_method' => 'sometimes|required|string|max:100',
                'amount' => 'sometimes|required|numeric|min:0',
                'payment_date' => 'sometimes|required|date',
                'status' => 'sometimes|required|string|in:pending,completed,failed',
            ]);

            $payment->fill($request->all());
            $payment->save();

            return response()->json([
                'code' => 200,
                'message' => 'Cập nhật thanh toán thành công',
                'data' => $payment
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Thanh toán không tồn tại'
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $payment = Payment::where('payment_id', $id)
                ->where('is_deleted', false)
                ->firstOrFail();

            $payment->is_deleted = true;
            $payment->save();

            return response()->json([
                'code' => 200,
                'message' => 'Xóa thanh toán thành công (soft delete)'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => 'Thanh toán không tồn tại'
            ]);
        }
    }
}

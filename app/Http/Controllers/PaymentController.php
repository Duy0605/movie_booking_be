<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Mail\New_movie_ticket;

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

    // Cập nhật trạng thái payment sang COMPLETED
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

        // Nếu đủ tiền, cho chuyển sang COMPLETED
        $payment->payment_status = 'COMPLETED';
        $payment->save();


        // Lấy thông tin booking và user
        $booking = $payment->booking;
        $user = $booking->user;

        // Lấy thông tin phim, suất chiếu, phòng, rạp
        $showtime = $booking->showtime;
        $movie = $showtime->movie ?? null;
        $room = $showtime->room ?? null;///
        $cinema = $room->cinema ?? null;///

        // Lấy danh sách ghế đã đặt
        $seats = $booking->bookingSeats->map(function ($bs) {
            return $bs->seat->seat_number ?? '';
        })->toArray();
        $seatsString = implode(', ', $seats);

        // Chuẩn bị dữ liệu gửi mail
        $customerName = $user->full_name ?? 'Khách hàng';
        $bookingId = $booking->booking_id;
        $movieTitle = $movie->title ?? 'Tên phim';
        $cinemaName = $cinema->name ?? 'Rạp chiếu';
        $roomName = $room->room_name ?? 'Phòng chiếu';
        $showtimeStr = $showtime ? $showtime->start_time : 'Thời gian chiếu';
        $totalPrice = $payment->amount;
        $barcodeUrl = $payment->barcode ?? '';
        $ticketCode = $payment->payment_id;

        Mail::to($user->email)->send(new New_movie_ticket(
            $customerName,
            $bookingId,
            $movieTitle,
            $cinemaName,
            $roomName,
            $showtimeStr,
            $seatsString,
            $totalPrice,
            $barcodeUrl,
            $ticketCode
        ));

        return response()->json(['code' => 200, 'message' => 'Cập nhật trạng thái sang COMPLETED thành công', 'data' => $payment]);
    }

    // Proxy PayOS API
    public function proxyPayOS(Request $request)
    {
        $payosClientId = env('PAYOS_CLIENT_ID');
        $payosApiKey = env('PAYOS_API_KEY');
        $payosApiUrl = env('PAYOS_API_URL');
        $checksumKey = env('PAYOS_CHECKSUM_KEY');

        // Prepare data for signature
        $data = $request->all();
        $signatureFields = [
            'amount' => $data['amount'],
            'cancelUrl' => $data['cancelUrl'],
            'description' => $data['description'],
            'orderCode' => $data['orderCode'],
            'returnUrl' => $data['returnUrl']
        ];

        // Sort fields alphabetically by key
        ksort($signatureFields);

        // Concatenate fields in the format key=value
        $signatureString = '';
        foreach ($signatureFields as $key => $value) {
            $signatureString .= "$key=$value&";
        }
        $signatureString = rtrim($signatureString, '&'); // Remove trailing &

        // Generate HMAC-SHA256 signature
        $signature = hash_hmac('sha256', $signatureString, $checksumKey);

        // Add signature to the request data
        $data['signature'] = $signature;


        $response = Http::withHeaders([
            'x-client-id' => $payosClientId,
            'x-api-key' => $payosApiKey,
            'Content-Type' => 'application/json',
        ])->post($payosApiUrl, $data);

        $responseData = $response->json();

        return response()->json($responseData)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    // Lấy thông tin link thanh toán từ PayOS
    public function getPaymentLinkInfo($orderCode)
    {
        $payosClientId = env('PAYOS_CLIENT_ID');
        $payosApiKey = env('PAYOS_API_KEY');
        $payosApiGetUrl = env('PAYOS_API_URL');

        // Kiểm tra biến môi trường
        if (!$payosClientId || !$payosApiKey || !$payosApiGetUrl) {
            \Log::error('Missing PayOS configuration for getPaymentLinkInfo');
            return response()->json([
                'code' => 500,
                'message' => 'Lỗi cấu hình server, vui lòng thử lại sau'
            ], 500);
        }

        try {
            \Log::info('Fetching PayOS payment link info:', ['orderCode' => $orderCode]);

            // Gửi yêu cầu GET tới PayOS API
            $response = Http::withHeaders([
                'x-client-id' => $payosClientId,
                'x-api-key' => $payosApiKey,
                'Content-Type' => 'application/json',
            ])->get("{$payosApiGetUrl}/{$orderCode}");

            $responseData = $response->json();
            \Log::info('PayOS Payment Link Response:', $responseData);

            // Kiểm tra phản hồi từ PayOS
            if ($response->successful() && isset($responseData['data'])) {
                return response()->json([
                    'code' => 200,
                    'message' => 'Lấy thông tin link thanh toán thành công',
                    'data' => $responseData['data']
                ]);
            } else {
                \Log::error('PayOS API error:', ['response' => $responseData]);
                return response()->json([
                    'code' => 400,
                    'message' => $responseData['desc'] ?? 'Không thể lấy thông tin link thanh toán'
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching PayOS payment link info:', [
                'orderCode' => $orderCode,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'code' => 500,
                'message' => 'Lỗi khi lấy thông tin link thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }
}

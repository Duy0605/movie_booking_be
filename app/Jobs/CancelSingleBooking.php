<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Booking;
use App\Models\BookingSeat;
use Illuminate\Support\Facades\Log;

class CancelSingleBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bookingId;

    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
    }

    public function handle()
    {
        \Log::info('Starting CancelSingleBooking job', ['booking_id' => $this->bookingId]);

        // Find the booking by booking_id (not id)
        $booking = Booking::where('booking_id', $this->bookingId)
            ->where('is_deleted', false)
            ->with('coupon')
            ->first();

        // If the booking doesn't exist or is already deleted, do nothing
        if (!$booking) {
            \Log::info("Booking not found or already deleted", ['booking_id' => $this->bookingId]);
            return;
        }

        \Log::info('Booking found', [
            'booking_id' => $this->bookingId,
            'status' => $booking->status,
            'coupon_id' => $booking->coupon_id,
        ]);

        // Check if the booking is still in PENDING status
        if ($booking->status !== 'PENDING') {
            \Log::info("Booking is not in PENDING status", [
                'booking_id' => $this->bookingId,
                'status' => $booking->status,
            ]);
            return;
        }

        // Cancel the booking
        $booking->status = 'CANCELLED';
        $booking->save();

        \Log::info('Booking status updated to CANCELLED', ['booking_id' => $this->bookingId]);

        // Soft delete associated booking seats
        BookingSeat::where('booking_id', $booking->id)
            ->where('is_deleted', false)
            ->update(['is_deleted' => true]);

        \Log::info('Booking seats soft-deleted', ['booking_id' => $this->bookingId]);

        // If the booking used a coupon, decrement its usage
        if ($booking->coupon) {
            \Log::info('Coupon associated with booking', [
                'booking_id' => $this->bookingId,
                'coupon_id' => $booking->coupon->coupon_id,
                'is_used' => $booking->coupon->is_used,
            ]);

            try {
                $coupon = $booking->coupon;
                if ($coupon->is_used > 0) {
                    $coupon->is_used -= 1;
                    $coupon->save();
                    \Log::info("Coupon usage decremented", [
                        'coupon_id' => $coupon->coupon_id,
                        'is_used' => $coupon->is_used,
                    ]);
                } else {
                    \Log::warning("Coupon usage is already 0, cannot decrement", [
                        'coupon_id' => $coupon->coupon_id,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error("Failed to decrement coupon usage", [
                    'coupon_id' => $booking->coupon->coupon_id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            \Log::info('No coupon associated with booking', ['booking_id' => $this->bookingId]);
        }

        \Log::info("Booking canceled successfully", ['booking_id' => $this->bookingId]);
    }
}
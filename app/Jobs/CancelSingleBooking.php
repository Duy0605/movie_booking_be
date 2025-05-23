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
        // Find the booking by ID
        $booking = Booking::where('booking_id', $this->bookingId)
            ->where('is_deleted', false)
            ->first();

        // If the booking doesn't exist or is already deleted, do nothing
        if (!$booking) {
            return;
        }

        // Check if the booking is still in PENDING status
        if ($booking->status !== 'PENDING') {
            return;
        }

        // Cancel the booking
        $booking->status = 'CANCELLED';
        $booking->save();

        // Soft delete associated booking seats
        BookingSeat::where('booking_id', $booking->booking_id)
            ->where('is_deleted', false)
            ->update(['is_deleted' => true]);
    }
}
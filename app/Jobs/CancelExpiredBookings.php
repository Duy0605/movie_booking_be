<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Booking;
use App\Models\BookingSeat;
use Carbon\Carbon;

class CancelExpiredBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        // // Find bookings that are PENDING and older than 5 minutes
        // $expirationTime = Carbon::now()->subMinutes(5);

        // $expiredBookings = Booking::where('status', 'PENDING')
        //     ->where('is_deleted', false)
        //     ->where('created_at', '<=', $expirationTime)
        //     ->get();

        // foreach ($expiredBookings as $booking) {
        //     // Update booking status to CANCELLED
        //     $booking->status = 'CANCELLED';
        //     $booking->save();

        //     // Soft delete associated booking seats
        //     BookingSeat::where('booking_id', $booking->booking_id)
        //         ->where('is_deleted', false)
        //         ->update(['is_deleted' => true]);

        //     \Log::info("Canceled booking ID {$booking->booking_id} at " . now()->toDateTimeString());
        // }

        // // Dispatch the job again with a 1-minute delay
        // CancelExpiredBookings::dispatch()->delay(now()->addMinute());
    }
}
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeatBookedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $showtimeId;
    public $seatId;
    public $seatNumber;
    public $bookingId;

    public function __construct($showtimeId, $seatId, $seatNumber, $bookingId)
    {
        $this->showtimeId = $showtimeId;
        $this->seatId = $seatId;
        $this->seatNumber = $seatNumber;
        $this->bookingId = $bookingId;
    }

    public function broadcastOn()
    {
        return new Channel('showtime.' . $this->showtimeId);
    }

    public function broadcastAs()
    {
        return 'seat.booked';
    }

    public function broadcastWith()
    {
        return [
            'showtime_id' => $this->showtimeId,
            'seat_id' => $this->seatId,
            'seat_number' => $this->seatNumber,
            'booking_id' => $this->bookingId,
        ];
    }
}
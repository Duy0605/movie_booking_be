<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSeat extends Model
{
    protected $table = 'booking_seat';
    protected $primaryKey = 'booking_seat_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'booking_seat_id',
        'booking_id',
        'seat_id',
        'is_deleted',
    ];
    protected $casts = [
    'is_deleted' => 'boolean',
];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }
}

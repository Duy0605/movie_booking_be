<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'booking';
    protected $primaryKey = 'booking_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'booking_id',
        'user_id',
        'showtime_id',
        'coupon_id', 
        'total_price',
        'status',
        'order_code',
        'barcode',
        'is_used',
        'is_deleted',
    ];

    public function user()
    {
        return $this->belongsTo(UserAccount::class, 'user_id', 'user_id');
    }

    public function showtime()
    {
        return $this->belongsTo(ShowTime::class, 'showtime_id', 'showtime_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function movie()
    {
        return $this->hasOneThrough(
            Movie::class,
            ShowTime::class,
            'showtime_id', 
            'movie_id',     
            'showtime_id',  
            'movie_id'     
        );
    }

    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class, 'booking_id', 'booking_id');
    }

    public function updateTotalPrice()
{
    $seatCount = $this->bookingSeats()->count();
    $pricePerSeat = $this->showtime->price; 

    $this->total_price = $seatCount * $pricePerSeat;
    $this->save();
}
}

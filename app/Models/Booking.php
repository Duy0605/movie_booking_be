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
        'total_price',
        'status',
        'is_deleted',
    ];

    public function user()
    {
        return $this->belongsTo(UserAccount::class, 'user_id', 'user_id');
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class, 'showtime_id', 'showtime_id');
    }

    // Lấy movie thông qua showtime
    public function movie()
    {
        return $this->hasOneThrough(
            Movie::class,
            Showtime::class,
            'showtime_id',  // khóa ngoại trên bảng showtime
            'movie_id',     // khóa chính bảng movie
            'showtime_id',  // khóa ngoại trên bảng booking
            'movie_id'      // khóa ngoại trên bảng showtime
        );
    }

    public function bookingSeats()
    {
        return $this->hasMany(BookingSeat::class, 'booking_id', 'booking_id');
    }

    public function updateTotalPrice()
{
    $seatCount = $this->bookingSeats()->count();
    $pricePerSeat = $this->showtime->price; // giả sử showtime có trường price

    $this->total_price = $seatCount * $pricePerSeat;
    $this->save();
}
}

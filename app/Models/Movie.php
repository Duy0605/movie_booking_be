<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $table = 'movie';
    protected $primaryKey = 'movie_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'movie_id',
        'title',
        'description',
        'duration',      // thời lượng (phút)
        'release_date',
        'director',
        'cast',          // danh sách diễn viên (chuỗi hoặc JSON)
        'genre',
        'rating',        // điểm đánh giá
        'poster_url',    // ảnh bìa
        'is_deleted'
    ];

    protected $casts = [
        'is_deleted' => 'boolean'
    ];

    // Quan hệ với booking (1 movie có nhiều booking)
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'movie_id', 'movie_id')
            ->where('is_deleted', false);
    }

    // Quan hệ với showtime (1 movie có nhiều showtime)
    public function showtimes()
    {
        return $this->hasMany(ShowTime::class, 'movie_id', 'movie_id')
            ->where('is_deleted', false);
    }
}
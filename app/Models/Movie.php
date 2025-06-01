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
        'duration',      
        'release_date',
        'director',
        'cast',          
        'genre',
        'rating',        
        'poster_url',   
        'is_deleted'
    ];

    protected $casts = [
        'is_deleted' => 'boolean'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'movie_id', 'movie_id')
            ->where('is_deleted', false);
    }

    public function showtimes()
    {
        return $this->hasMany(ShowTime::class, 'movie_id', 'movie_id')
            ->where('is_deleted', false);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowTime extends Model
{
    protected $table = 'showtime';
    protected $primaryKey = 'showtime_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'showtime_id',
        'movie_id',
        'room_id',
        'start_time',
        'price',
        'is_deleted',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'start_time' => 'datetime',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id')
            ->where('is_deleted', false);
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id')
            ->where('is_deleted', false)
            ->with('cinema'); // Eager-load the cinema relationship from the Room model
    }
}

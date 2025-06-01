<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Seat extends Model
{
    use HasFactory;

    protected $table = 'seat'; 
    protected $primaryKey = 'seat_id'; 
    public $incrementing = false; 
    protected $keyType = 'string'; 

    protected $fillable = [
        'seat_id',
        'room_id',
        'seat_number',
        'seat_type',
        'is_deleted',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

   
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }
}

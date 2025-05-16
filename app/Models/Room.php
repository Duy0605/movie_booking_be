<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'room'; 

    protected $primaryKey = 'room_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'room_id', 'cinema_id', 'room_name', 'capacity', 'is_deleted'
    ];
}

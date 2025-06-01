<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cinema extends Model
{
    use HasFactory;

    protected $table = 'cinema'; 

    protected $primaryKey = 'cinema_id'; 

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'cinema_id',
        'name',
        'address',
        'created_at',
        'updated_at',
        'is_deleted',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'cinema_id', 'cinema_id');
    }
}

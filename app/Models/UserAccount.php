<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccount extends Model
{
    use HasFactory;

    protected $table = 'user_account';
    protected $primaryKey = 'user_id';
    public $incrementing = false; 
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'username',
        'email',
        'password', 
        'full_name',
        'dob',
        'profile_picture_url',
        'phone',
        'role',
        'api_token',
        'is_deleted',
    ];

    protected $hidden = [
        'password',
        'api_token',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'dob' => 'datetime',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }
}

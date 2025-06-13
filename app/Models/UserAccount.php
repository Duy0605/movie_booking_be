<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserAccount extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

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
        'is_deleted',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'dob' => 'datetime',
        'password' => 'hashed',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
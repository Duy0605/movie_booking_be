<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAccount extends Model
{
    use HasFactory;

    protected $table = 'user_account';
    protected $primaryKey = 'user_id';
    public $incrementing = false; // Vì user_id là kiểu UUID (varchar)
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'username',
        'email',
        'password',
        'full_name',
        'profile_picture_url',
        'phone',
        'is_deleted',
    ];

    protected $hidden = [
        'password',
    ];

    // Quan hệ với các bảng khác
    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }

    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id')
    //                 ->withTimestamps();
    // }

    // public function bookings()
    // {
    //     return $this->hasMany(Booking::class, 'user_id', 'user_id');
    // }

    // public function transactionLogs()
    // {
    //     return $this->hasMany(TransactionLog::class, 'user_id', 'user_id');
    // }
}

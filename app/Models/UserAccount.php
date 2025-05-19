<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccount extends Model
{
    use HasFactory;

    protected $table = 'user_account';

    protected $primaryKey = 'user_id';

    public $incrementing = false; // Vì dùng UUID

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
        'is_deleted',
    ];

    protected $hidden = [
        'password',
        'api_token',
    ];

    // Mối quan hệ với bảng role qua bảng trung gian user_role
    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id')
    //                 ->withTimestamps();
    // }

    // Một người dùng có thể có nhiều lượt đặt vé
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'user_id');
    }

    // Một người dùng có thể có nhiều đánh giá phim
    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }

    // Một người dùng có thể có nhiều giao dịch
    // public function transactionLogs()
    // // {
    // //     return $this->hasMany(TransactionLog::class, 'user_id', 'user_id');
    // // }
}

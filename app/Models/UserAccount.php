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
        'password', // Laravel mặc định dùng 'password' (dù DB bạn đặt là 'password_hash', nên cần xem lại)
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

    // Một người dùng có thể có nhiều lịch sử giao dịch
    // public function transactionLogs()
    // {
    //     return $this->hasMany(TransactionLog::class, 'user_id', 'user_id');
    // }
}

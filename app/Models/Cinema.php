<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cinema extends Model
{
    use HasFactory;

    protected $table = 'cinema'; // Tên bảng

    protected $primaryKey = 'cinema_id'; // Khóa chính

    public $incrementing = false; // Do sử dụng UUID, không auto-increment

    protected $keyType = 'string'; // Kiểu của primary key là chuỗi

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

    // Quan hệ: 1 cinema có nhiều room
    public function rooms()
    {
        return $this->hasMany(Room::class, 'cinema_id', 'cinema_id');
    }
}

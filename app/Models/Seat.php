<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Seat extends Model
{
    use HasFactory;

    protected $table = 'seat'; // Tên bảng trong CSDL
    protected $primaryKey = 'seat_id'; // Khóa chính
    public $incrementing = false; // Vì dùng UUID nên không tự tăng
    protected $keyType = 'string'; // Kiểu dữ liệu của khóa chính

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

    // Quan hệ: Một ghế thuộc về một phòng (room)
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }
}

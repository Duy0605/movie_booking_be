<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupon';
    protected $primaryKey = 'coupon_id';
    public $incrementing = false; // Vì coupon_id là string
    protected $keyType = 'string';

    protected $fillable = [
        'coupon_id',
        'code',
        'discount',
        'expiry_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expiry_date' => 'datetime',
    ];
}

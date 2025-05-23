<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'setting';
    protected $primaryKey = 'setting_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'setting_id',
        'name',
        'vip',
        'couple',
        'banner',
        'is_deleted',
    ];

    protected $casts = [
        'vip' => 'double',
        'couple' => 'double',
        'is_deleted' => 'boolean',
        'banner' => 'array', 
    ];
}
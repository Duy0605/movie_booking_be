<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'review';
    protected $primaryKey = 'review_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'review_id',
        'user_id',
        'movie_id',
        'rating',
        'comment',
        'is_deleted',
    ];
    public function user()
{
    return $this->belongsTo(UserAccount::class, 'user_id', 'user_id');
}
}

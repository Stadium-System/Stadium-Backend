<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stadium extends Model
{
    protected $fillable = [
        'name',
        'location',
        'latitude',
        'longitude',
        'price_per_hour',
        'capacity',
        'image',
        'description',
        'rating',
        'status',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}

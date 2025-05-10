<?php

namespace App\Models;
use App\Traits\HasImages;

use Illuminate\Database\Eloquent\Model;

class Stadium extends Model
{
    use HasImages;
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

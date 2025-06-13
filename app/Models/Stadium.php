<?php

namespace App\Models;
use App\Traits\Favoritable;
use App\Traits\HasImages;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Stadium extends Model implements HasMedia
{
    use InteractsWithMedia, Favoritable;
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

    // ================= Relationships ====================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }


}

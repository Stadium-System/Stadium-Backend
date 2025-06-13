<?php

namespace App\Models;

use App\Traits\Favoritable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Event extends Model implements HasMedia
{
    use InteractsWithMedia, Favoritable;
    
    protected $fillable = [
        'name',
        'description',
        'date',
        'image',
        'status',
        'stadium_id',
        'user_id'
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    // ================= Relationships ====================
    public function stadium()
    {
        return $this->belongsTo(Stadium::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }
}
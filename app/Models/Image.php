<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    protected $fillable = [
        'imageable_id',
        'imageable_type',
        'url',
        'type',
    ];

    /**
     * Get the owning imageable model.
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Boot method to handle model events.
     */
    protected static function booted()
    {
        static::deleting(function ($image) {
            \Storage::disk('s3')->delete($image->url);
        });
    }
}

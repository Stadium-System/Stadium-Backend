<?php

namespace App\Traits;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Favoritable
{
    /**
     * Get all favorites for the model.
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /**
     * Check if the model is favorited by a specific user.
     */
    public function isFavoritedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->favorites()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Get the favorites count.
     */
    public function getFavoritesCountAttribute(): int
    {
        return $this->favorites()->count();
    }

    /**
     * Add the model to user's favorites.
     */
    public function favoriteBy(User $user): void
    {
        $this->favorites()->firstOrCreate([
            'user_id' => $user->id,
        ]);
    }

    /**
     * Remove the model from user's favorites.
     */
    public function unfavoriteBy(User $user): void
    {
        $this->favorites()
            ->where('user_id', $user->id)
            ->delete();
    }
}
<?php

namespace App\Helpers;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaHelper
{
    /**
     * Attach media from temporary uploads to a model
     * 
     * @param HasMedia $model The model to attach media to
     * @param array|null $mediaIds Array of media IDs to attach
     * @param string $collectionName Name of the media collection
     * @param bool $clearExisting Whether to clear existing media in the collection
     * 
     * @return void
     */
    public static function attachMedia(HasMedia $model, ?array $mediaIds, string $collectionName, bool $clearExisting = false)
    {
        if (empty($mediaIds)) {
            return;
        }
        
        if ($clearExisting) {
            $model->clearMediaCollection($collectionName);
        }
        
        foreach ($mediaIds as $mediaId) {
            $media = Media::findOrFail($mediaId);
            $media->move($model, $collectionName);
        }
    }
}
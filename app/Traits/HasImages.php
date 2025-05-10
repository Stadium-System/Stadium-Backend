<?php

namespace App\Traits;

use App\Models\Image;
use App\Models\TempUpload;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait HasImages
{
    /**
     * Get all images associated with the model.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Associate multiple images from TempUploads.
     *
     * @param array $tempUploadIds
     * @param string $path
     * @throws \Exception
     */
    public function imagesFromTempUploads(array $tempUploadIds, string $path)
    {
        foreach ($tempUploadIds as $tempUploadId) {
            $tempUpload = TempUpload::findOrFail($tempUploadId);

            // Ensure the file exists
            if (!Storage::disk('s3')->exists($tempUpload->url)) {
                throw new \Exception("Source file does not exist: {$tempUpload->url}");
            }

            // Generate a unique path for the image
            $fileName = uniqid() . '_' . basename($tempUpload->url);
            $newPath = "$path/{$this->id}/$fileName";

            // Copy the file to the new location
            if (!Storage::disk('s3')->copy($tempUpload->url, $newPath)) {
                throw new \Exception("Cannot copy image from {$tempUpload->url} to {$newPath}");
            }

            // Delete the original file
            Storage::disk('s3')->delete($tempUpload->url);

            // Associate the image with the model
            $this->images()->create(['url' => $newPath]);

            // Delete the TempUpload record
            $tempUpload->delete();
        }

        // Reload the images relationship
        $this->load('images');
    }

    /**
     * Handle image deletion when the parent model is deleted.
     */
    protected static function bootHasImages()
    {
        static::deleting(function ($model) {
            foreach ($model->images as $image) {
                Storage::disk('s3')->delete($image->url);
                $image->delete();
            }
        });
    }
}
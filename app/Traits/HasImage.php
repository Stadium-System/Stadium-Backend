<?php

namespace App\Traits;

use App\Models\Image;
use App\Models\TempUpload;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

trait HasImage
{
    /**
     * Get the primary image associated with the model.
     */
    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->where('type', 'primary');
    }

    /**
     * Associate an image from a TempUpload record.
     *
     * @param int $tempUploadId
     * @param string $path
     * @throws \Exception
     */
    public function imageFromTempUpload(int $tempUploadId, string $path)
    {
        $tempUpload = TempUpload::findOrFail($tempUploadId);

        // Ensure the file exists
        if (!Storage::disk('s3')->exists($tempUpload->url)) {
            throw new \Exception("Source file does not exist: {$tempUpload->url}");
        }

        // Extract file name and construct the new path
        $fileName = uniqid() . '_' . basename($tempUpload->url);
        $newPath = "$path/{$this->id}/$fileName";

        // Copy the file to the new location
        if (!Storage::disk('s3')->copy($tempUpload->url, $newPath)) {
            throw new \Exception("Cannot copy image from {$tempUpload->url} to {$newPath}");
        }

        // Delete the original file
        Storage::disk('s3')->delete($tempUpload->url);

        // Update or create the associated image
        if ($this->image) {
            $oldImageUrl = $this->image->url;
            $this->image->update(['url' => $newPath]);
            Storage::disk('s3')->delete($oldImageUrl);
        } else {
            $this->image()->create(['url' => $newPath, 'type' => 'primary']);
        }

        // Delete the TempUpload record
        $tempUpload->delete();

        // Reload the image relationship
        $this->load('image');
    }

    /**
     * Handle image deletion when the parent model is deleted.
     */
    protected static function bootHasImage()
    {
        static::deleting(function ($model) {
            if ($model->image) {
                Storage::disk('s3')->delete($model->image->url);
                $model->image->delete();
            }
        });
    }
}
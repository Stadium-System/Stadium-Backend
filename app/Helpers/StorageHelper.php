<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    public static function uploadToS3($file, $directory)
    {
        $path = Storage::disk('s3')->put($directory, $file, 'public');
        if (!$path) {
            throw new \Exception("Failed to upload file to S3.");
        }
        return [
            'path' => $path,
            'url' => Storage::disk('s3')->url($path),
        ];
    }
}
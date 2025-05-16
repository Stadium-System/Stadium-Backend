<?php

namespace App\Http\Controllers;

use App\Helpers\StorageHelper;
use App\Models\TempUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TempUploadController extends Controller
{
    /**
     * @group File Upload
     *
     * Upload Image
     *
     * Uploads a temporary image file to S3 and returns a temporary upload ID.    
     * @authenticated
     *
     * @bodyParam file file required The image file to upload.
     *
     * @response 201 {
     *  "message": "Uploaded Successfully",
     *  "data": {
     *      "id": 2,
     *      "url": "https://your-s3-bucket-url.com/temp-uploads/images/unique_image_name.jpg"
     *  }
     * }
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $tempModel = TempUpload::create(['type' => 'image']);

        $media = $tempModel->addMediaFromRequest('file')
            ->usingName('temp_' . time())
            ->toMediaCollection('temp_images');

        return response()->json([
            'message' => 'Uploaded Successfully',
            'data' => [
                'id' => $media->id,
                'url' => $media->getFullUrl()
            ],
        ], 201);
    }

    /**
     * Get all temporary uploads for the authenticated user
     */
    public function listTempUploads()
    {
        $tempUploads = TempUpload::all();
        
        $mediaItems = collect();
        
        // Collect all media items from all temp uploads
        foreach ($tempUploads as $tempUpload) {
            $mediaItems = $mediaItems->merge($tempUpload->getMedia('temp_images'));
        }
        
        return response()->json([
            'count' => $mediaItems->count(),
            'data' => $mediaItems->map(function($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getFullUrl(),
                    'created_at' => $media->created_at
                ];
            })
        ]);
    }
}
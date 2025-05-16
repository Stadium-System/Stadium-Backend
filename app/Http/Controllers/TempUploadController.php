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
     * Uploads a temporary image file to S3 and returns a media ID that can be used in other endpoints.
     * This endpoint must be called first before creating/updating resources with images. 
     *  
     * @authenticated
     *
     * @bodyParam file file required The image file to upload. Must be jpeg, png, jpg, or gif and less than 2MB.
     *
     * @response 201 {
     *  "message": "Uploaded Successfully",
     *  "data": {
     *      "id": 8,
     *      "url": "https://stadium-app-bucket-gaafarbbk.s3.us-east-2.amazonaws.com/8/146.jpg"
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
     * @group File Upload
     *
     * List Temporary Uploads
     *
     * Retrieves all temporary media uploads for the authenticated user.
     * Use this endpoint to see all previously uploaded temporary files that haven't been attached to models yet.
     * 
     * @authenticated
     *
     * @response {
     *  "count": 3,
     *  "data": [
     *    {
     *      "id": 1,
     *      "url": "https://your-s3-bucket-url.com/temp-uploads/images/first_image.jpg",
     *      "created_at": "2025-05-09T10:00:00.000000Z"
     *    },
     *    {
     *      "id": 2,
     *      "url": "https://your-s3-bucket-url.com/temp-uploads/images/second_image.jpg",
     *      "created_at": "2025-05-09T10:15:00.000000Z"
     *    },
     *    {
     *      "id": 3,
     *      "url": "https://your-s3-bucket-url.com/temp-uploads/images/third_image.jpg",
     *      "created_at": "2025-05-09T10:30:00.000000Z"
     *    }
     *  ]
     * }
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
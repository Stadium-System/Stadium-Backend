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

        $uploadData = StorageHelper::uploadToS3($request->file('file'), 'temp-uploads/images');
        $upload = TempUpload::create([
            'url' => $uploadData['path'],
            'type' => 'image',
        ]);
        
        return response()->json([
            'message' => 'Uploaded Successfully',
            'data' => [
                'id' => $upload->id,
                'url' => Storage::disk('s3')->url($upload->url),
            ],
        ], 201);
    }
}
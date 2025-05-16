<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TempUploadController;

// Routes accessible by both admin and user (with authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Temporary image upload to S3
    Route::post('/temp-uploads/images', [TempUploadController::class, 'uploadImage']);
    // List all temporary uploads for the authenticated user
    Route::get('/temp-uploads', [TempUploadController::class, 'listTempUploads']);
    
});
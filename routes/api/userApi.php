<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\OtpController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\StadiumController;
use App\Http\Controllers\TempUploadController;
use App\Http\Controllers\User\EventController;
use App\Http\Controllers\User\FavoriteController;

// Authentication routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// OTP routes with throttling
Route::middleware('throttle:otp')->group(function () {
    Route::post('/otp/send', [OtpController::class, 'sendOtp']);
    Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
    Route::post('/password/reset', [OtpController::class, 'resetPassword']);
});

// Protected user routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User profile management
    Route::get('/me', [UserController::class, 'getMe']);
    Route::put('/me', [UserController::class, 'update']);
    Route::delete('/me', [UserController::class, 'destroy']);

    // Event catalog 
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    
    // Stadium catalog 
    Route::get('/stadiums', [StadiumController::class, 'index']);
    Route::get('/stadiums/{stadium}', [StadiumController::class, 'show']);

    // Favorites management
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::get('/stats', [FavoriteController::class, 'stats']);
        
        Route::post('/stadiums/{stadium}', [FavoriteController::class, 'favoriteStadium']);
        Route::delete('/stadiums/{stadium}', [FavoriteController::class, 'unfavoriteStadium']);
        
        Route::post('/events/{event}', [FavoriteController::class, 'favoriteEvent']);
        Route::delete('/events/{event}', [FavoriteController::class, 'unfavoriteEvent']);
     });
    
    // Stadium management (only for users with 'owner' role)
    Route::middleware(['role:owner'])->group(function () {
        Route::post('/stadiums', [StadiumController::class, 'store']);
        Route::put('/stadiums/{stadium}', [StadiumController::class, 'update']);
        Route::delete('/stadiums/{stadium}', [StadiumController::class, 'destroy']);
        Route::delete('/stadiums/{stadium}/images/{image}', [StadiumController::class, 'removeImage']);

        // Event management (only for users with 'owner' role)
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
        Route::delete('/events/{event}/images/{image}', [EventController::class, 'removeImage']);
    });
});
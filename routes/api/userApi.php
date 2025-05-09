<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\OtpController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\StadiumController;

// Authentication routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// OTP routes with throttling
Route::middleware('throttle:otp')->group(function () {
    Route::post('/otp/send', [OtpController::class, 'sendOtp']);
    Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
});

// Protected user routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User profile management
    Route::get('/me', [UserController::class, 'getMe']);
    Route::put('/me', [UserController::class, 'update']);
    Route::delete('/me', [UserController::class, 'destroy']);
    
    // Stadium catalog (available to all authenticated users)
    Route::get('/stadiums', [StadiumController::class, 'index']);
    Route::get('/stadiums/{stadium}', [StadiumController::class, 'show']);
    
    // Stadium management (only for users with 'owner' role)
    Route::middleware(['role:owner'])->group(function () {
        Route::post('/stadiums', [StadiumController::class, 'store']);
        Route::put('/stadiums/{stadium}', [StadiumController::class, 'update']);
        Route::delete('/stadiums/{stadium}', [StadiumController::class, 'destroy']);
    });
});
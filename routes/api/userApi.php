<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\OtpController;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('throttle:otp')->group(function () {
    Route::post('/otp/send', [OtpController::class, 'sendOtp']);
    Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
});
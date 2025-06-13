<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StadiumController;
use App\Http\Controllers\TempUploadController;
use App\Http\Controllers\Admin\EventController;

// Public admin routes
Route::post('/login', [AuthController::class, 'login']);

// Protected admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Authentication
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);


    // User management
    Route::apiResource('users', UserController::class);
    Route::patch('/users/{user}/ban', [UserController::class, 'ban']);
    Route::patch('/users/{user}/unban', [UserController::class, 'unban']);
    
    // Stadium management
    Route::apiResource('stadiums', StadiumController::class);
    Route::delete('/stadiums/{stadium}/images/{image}', [StadiumController::class, 'removeImage']);

    // Event management
    Route::apiResource('events', EventController::class);
    Route::delete('/events/{event}/images/{image}', [EventController::class, 'removeImage']);

});

Route::post('run-command', function (Request $request) {
    $command = $request->input('command');
    
    $output = Artisan::call($command, ['--force' => true, '--verbose' => true]);
    return response()->json(['output' => $output]);
});

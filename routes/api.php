<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [\App\Http\Controllers\AuthController::class, 'register'])
    ->middleware('throttle:auth-register');
Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login'])
    ->middleware('throttle:auth-login');

// Password Reset Routes
Route::post('/auth/password-forgot', [\App\Http\Controllers\PasswordResetController::class, 'sendOtp'])
    ->middleware('throttle:3,1'); // max 3 attempts per minute
Route::post('/auth/password-reset-verify', [\App\Http\Controllers\PasswordResetController::class, 'verifyOtp']);
Route::post('/auth/password-reset', [\App\Http\Controllers\PasswordResetController::class, 'resetPassword']);

Route::middleware(['auth:sanctum', 'throttle:api-authenticated'])->group(function () {
    Route::get('/user/me', function (Request $request) {
        return $request->user();
    });

    Route::put('/user/update', [\App\Http\Controllers\AccountController::class, 'update']);
    Route::post('/user/photo', [\App\Http\Controllers\AccountController::class, 'uploadPhoto']);
    Route::delete('/user/photo', [\App\Http\Controllers\AccountController::class, 'deletePhoto']);
    Route::delete('/user/delete', [\App\Http\Controllers\AccountController::class, 'deleteAccount'])
        ->middleware('throttle:account-delete');

    Route::get('/auth/verify', function (Request $request) {
        return response()->json(['success' => true]);
    });

    // Logout — revoke current token
    Route::post('/auth/logout', function (Request $request) {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    });

    // Task statistics (must be before apiResource to avoid route conflict)
    Route::get('/tasks/stats', [\App\Http\Controllers\TaskController::class, 'stats']);

    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
    Route::apiResource('tasks', \App\Http\Controllers\TaskController::class);
});

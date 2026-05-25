<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\PasswordController; // لا تنسَ إضافة هذا السطر في الأعلى

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// مسار تسجيل الدخول
Route::post('/auth/login', [LoginController::class, 'login']);

// مسارات استعادة كلمة المرور (عامة - خارج الميدلوير)
Route::prefix('auth/password')->group(function () {

    Route::post('/send-otp', [PasswordController::class, 'sendResetOtp']);
    Route::post('/reset', [PasswordController::class, 'resetPassword']);
});

// المسارات المحمية بالتوكن
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/auth/logout', [LoginController::class, 'logout']);
    
    Route::get('/user/profile', function (Request $request) {
        return response()->json([
            'status' => true,
            'user' => $request->user()
        ]);
    });
});
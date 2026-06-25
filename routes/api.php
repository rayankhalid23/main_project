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
// مسارات استعادة كلمة المرور (عامة)
Route::prefix('auth/password')->group(function () {
    Route::post('/send-otp', [PasswordController::class, 'sendResetOtp']); // 1. إرسال الكود
    Route::post('/verify-otp', [PasswordController::class, 'verifyOtp']);  // 2. التحقق من الكود (الجديدة)
    Route::post('/reset', [PasswordController::class, 'resetPassword']);   // 3. تغيير كلمة المرور
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
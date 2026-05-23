<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;



/*
|--------------------------------------------------------------------------
| API Routes - نظام تسجيل الدخول المباشر
|--------------------------------------------------------------------------
*/

// مسار تسجيل الدخول المباشر (رقم الهاتف + كلمة المرور)
Route::post('/auth/login', [LoginController::class, 'login']);



// المسارات المحمية بالتوكن (تفتح بعد تسجيل الدخول بنجاح)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [LoginController::class, 'logout']);
    
    // مسار تجريبي لجلب بيانات المستخدم الحالي عبر التوكن
    Route::get('/user/profile', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'status' => true,
            'user' => $request->user()
        ]);
    });
    
});
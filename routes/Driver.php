<?php

use App\Http\Controllers\Api\Driver\DriverRegisterController;
use App\Http\Controllers\Api\Driver\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver Routes
|--------------------------------------------------------------------------
|
| ملف المسارات الخاص بكيانات وعمليات السائقين وفقاً لهيكلية المشروع المعتمدة.
|
*/

// مسار تسجيل سائق جديد في النظام (لا يتطلب تسجيل دخول مسبق)
Route::post('driver/register', [DriverRegisterController::class, 'register'])
    ->name('api.driver.register');
    // مسارات تتطلب تسجيل الدخول (سائق معتمد)
Route::middleware('auth:sanctum')->group(function () {


    Route::get('driver/profile', [ProfileController::class, 'show'])
        ->name('api.driver.profile.show');
    
    // استخدمنا POST بدلاً من PUT أو PATCH لأن لارافيل يتعامل بشكل أفضل 
    // مع الملفات المرفوعة (مثل الصورة الشخصية) عند استخدام POST مع form-data
    Route::post('driver/profile/update', [ProfileController::class, 'update'])
        ->name('api.driver.profile.update');
        
});
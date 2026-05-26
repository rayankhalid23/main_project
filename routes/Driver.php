<?php

use App\Http\Controllers\Api\Driver\DriverRegisterController;
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
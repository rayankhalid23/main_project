<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Driver\DriverRegisterController;
use App\Http\Controllers\Api\Driver\ProfileController;
use App\Http\Controllers\Api\Driver\DriverPreferenceController;

/*
|--------------------------------------------------------------------------
| Driver Routes (تم إزالة الـ prefix التكراري ليطابق v1/driver مباشرة)
|--------------------------------------------------------------------------
*/

// --- 🔓 مسارات مفتوحة: لا تتطلب تسجيل دخول مسبق ---

    
    // 1. طلب إرسال كود الـ OTP إلى بريد السائق
    Route::post('register', [DriverRegisterController::class, 'registerAccount'])
        ->name('api.driver.register.account');

    // 2. التحقق من كود الـ OTP وإنشاء الحساب وتوليد التوكن فوراً
    Route::post('verify-otp', [DriverRegisterController::class, 'verifyOtp'])
        ->name('api.driver.verify-otp');

    // روابط تأكيد وإلغاء البريد الإلكتروني (تعتمد على التوقيع الرقمي المحمي Signed URL)
    Route::get('email/approve/{id}', [ProfileController::class, 'approveEmailChange'])
        ->name('api.driver.email.approve')
        ->middleware('signed');

    Route::get('email/reject/{id}', [ProfileController::class, 'rejectEmailChange'])
        ->name('api.driver.email.reject')
        ->middleware('signed');


// --- 🔒 مسارات محمية: تتطلب توكن Sanctum ---
Route::middleware('auth:sanctum')->group(function () {
    
    // 3. المرحلة الثانية من التسجيل: رفع الوثائق وبيانات المركبة لإكمال الملف الشخصي
    Route::post('complete-profile/{userId}', [DriverRegisterController::class, 'completeProfile'])
        ->name('api.driver.complete-profile');

    // عرض بيانات الملف الشخصي للسائق وعلاقاته
    Route::get('profile', [ProfileController::class, 'show'])
        ->name('api.driver.profile.show');
    
    // تحديث البيانات الشخصية والمظهر
    Route::post('profile/update', [ProfileController::class, 'update'])
        ->name('api.driver.profile.update');

    // تحديث وتجديد المستندات والوثائق الرسمية
    Route::post('profile/legal-data', [ProfileController::class, 'updateLegalData'])
        ->name('api.driver.profile.legal-data');

    // تحديث بيانات وتفاصيل المركبة
    Route::post('profile/vehicle/{vehicle}', [ProfileController::class, 'updateVehicle'])
        ->name('api.driver.profile.vehicle.update');

    // 🗺️ مسارات تفضيلات العمل ومناطق الجغرافيا للسائق
    Route::get('preferences', [DriverPreferenceController::class, 'show'])
        ->name('api.driver.preferences.show');
        
    Route::post('preferences', [DriverPreferenceController::class, 'update'])
        ->name('api.driver.preferences.update');
        
    Route::post('preferences/zones/add', [DriverPreferenceController::class, 'addZone'])
        ->name('api.driver.preferences.zones.add');
        
    Route::post('preferences/zones/remove', [DriverPreferenceController::class, 'removeZone'])
        ->name('api.driver.preferences.zones.remove');
        
    Route::get('preferences/defaults', [DriverPreferenceController::class, 'defaults'])
        ->name('api.driver.preferences.defaults');
});
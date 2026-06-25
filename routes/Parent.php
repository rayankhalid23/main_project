<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Parent\ParentAuthController;
use App\Http\Controllers\Api\Parent\ChildrenController;
use App\Http\Controllers\Api\Parent\AddressController;
use App\Http\Controllers\Api\Parent\ParentSchoolController;
use App\Http\Controllers\Api\Admin\ZoneController;
use App\Http\Controllers\Api\Parent\ParentDriverSearchController;

/*
|--------------------------------------------------------------------------
| Parent Routes
|--------------------------------------------------------------------------
*/

// 1. مسارات المصادقة العامة والتحقق (بدون توكن)
Route::prefix('parent')->group(function () {
    Route::post('/send-otp', [ParentAuthController::class, 'sendOtp']);
    Route::post('/register', [ParentAuthController::class, 'register']);

    /*
     * 🚀 [إضافة هندسية هامة]: مسارات الروابط الموقّعة (Signed URLs) لتأكيد أو رفض تغيير البريد الإلكتروني لولي الأمر.
     * تم وضعها هنا لأنها تُفتح مباشرة من صندوق الوارد (Email Inboxes) للمتصفح دون الحاجة لتوكن Bearer.
     * محمية بالـ Middleware 'signed' المدمج في لارافل لمنع التلاعب بالـ ID أو الرابط.
     */
    Route::middleware('signed')->group(function () {
        Route::get('/profile/email/approve/{id}', [ParentAuthController::class, 'approveEmailChange'])
            ->name('parent.profile.email.approve');
            
        Route::get('/profile/email/reject/{id}', [ParentAuthController::class, 'rejectEmailChange'])
            ->name('parent.profile.email.reject');
    });
});

// 2. مسارات ولي الأمر المحمية (تتطلب تسجيل دخول وتوكن Sanctum)
Route::middleware('auth:sanctum')->prefix('parent')->group(function () {
    
    // إدارة الحساب الشخصي لولي الأمر
    Route::get('/profile', [ParentAuthController::class, 'getProfile']);
    Route::post('/profile/update', [ParentAuthController::class, 'updateProfile']); // يدعم التعديل الجزئي المطور
    
    // إدارة الأبناء والطلبة المضافين
    Route::prefix('children')->group(function () {
        Route::get('/', [ChildrenController::class, 'index']);
        Route::post('/', [ChildrenController::class, 'store']);
        Route::get('/{id}', [ChildrenController::class, 'show']);
        Route::post('/{id}', [ChildrenController::class, 'update']);
        Route::delete('/{id}', [ChildrenController::class, 'destroy']);
    });

   // مسارات إدارة العناوين والمواقع الجغرافية لولي الأمر
   Route::prefix('addresses')->group(function () {
    Route::get('/', [AddressController::class, 'index']);
    Route::post('/', [AddressController::class, 'store']);
    
    // أضفنا withTrashed لكي يستطيع لارافيل التعرف على السجل وتحديثه أو حذفه ناعماً بدون كراش 404
    Route::post('/{address}', [AddressController::class, 'update'])->withTrashed(); 
    Route::delete('/{address}', [AddressController::class, 'destroy'])->withTrashed(); 
});
    

    // اقتراح مدرسة جديدة من قبل ولي الأمر للإدارة لمراجعتها
    Route::post('/suggest-school', [ParentSchoolController::class, 'store']);
    // جلب المناطق المتاحة بالنظام لولي الأمر
    Route::get('zones', [ZoneController::class, 'index']);

    Route::middleware(['auth:sanctum'])->group(function () {
    
        // مسار بحث وفلترة السائقين المتقدم لولي الأمر
        Route::post('drivers/search', [ParentDriverSearchController::class, 'index']);
        
    });

 
});
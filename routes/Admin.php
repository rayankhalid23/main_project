<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\SchoolController;

Route::middleware(['auth:sanctum'])->group(function () {
    
    // --- مجموعة روابط المشرفين (كما هي) ---
    Route::prefix('admins')->group(function () {
        Route::get('/', [AdminController::class, 'index']);
        Route::post('/', [AdminController::class, 'store']);
        Route::get('/{id}', [AdminController::class, 'show']);
        Route::post('/{id}', [AdminController::class, 'update']);
    });

    // --- إضافة روابط التحكم في السائقين هنا ---
    Route::prefix('drivers')->group(function () {
        // رابط تحديث حالة السائق (قبول أو رفض)
        // سيصبح الرابط: api/admin/drivers/{id}/status
        Route::post('/{id}/status', [AdminController::class, 'updateDriverStatus']);
    });

    Route::get('/schools', [SchoolController::class, 'index']);      // جلب الكل (المعتمد والمعلق للمراجعة)
Route::post('/schools', [SchoolController::class, 'store']);     // إضافة مدرسة معتمدة مباشرة
Route::get('/schools/{school}', [SchoolController::class, 'show']);
Route::post('/schools/{school}', [SchoolController::class, 'update']); // تحديث أو تغيير الحالة إلى approved
Route::delete('/schools/{school}', [SchoolController::class, 'destroy']);
});
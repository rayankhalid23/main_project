<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AdminController;

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
});
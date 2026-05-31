<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AdminController;

Route::middleware(['auth:sanctum'])->group(function () {
    // الروابط ستصبح تلقائياً: api/admin/admins/...
    Route::prefix('admins')->group(function () {
        Route::get('/', [AdminController::class, 'index']);
        Route::post('/', [AdminController::class, 'store']);
        Route::get('/{id}', [AdminController::class, 'show']);
        Route::post('/{id}', [AdminController::class, 'update']);
    });
});
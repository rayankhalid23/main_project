<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Parent\ParentSubscriptionController;
use App\Http\Controllers\API\Driver\DriverSubscriptionController;
use App\Http\Controllers\API\Shared\ContractController; 

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('contracts')->group(function () {
        Route::get('/clauses', [ContractController::class, 'clauses']);
        Route::post('/', [ContractController::class, 'store']);
        Route::get('/{id}', [ContractController::class, 'show']);
        Route::put('/{id}/accept', [ContractController::class, 'accept']);
        Route::put('/{id}/reject', [ContractController::class, 'reject']);
    });
    // مسارات أولياء الأمور لإرسال واستعراض طلبات الاشتراك
    Route::prefix('parent')->group(function () {
        Route::get('/', [ParentSubscriptionController::class, 'index']);  
        Route::post('/', [ParentSubscriptionController::class, 'store']); 
    });

    // مسارات السائقين لاستقبال والرد على طلبات الاشتراك
    Route::prefix('driver')->group(function () {
        Route::get('/', [DriverSubscriptionController::class, 'index']);                 
        Route::put('{id}/status', [DriverSubscriptionController::class, 'updateStatus']); 
    });
});
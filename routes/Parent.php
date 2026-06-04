<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Parent\ParentAuthController;
use App\Http\Controllers\Api\Parent\ChildrenController;
use App\Http\Controllers\Api\Parent\AddressController;
use App\Http\Controllers\Api\Parent\ParentSchoolController;

// 1. المسارات العامة (بدون توكن)
Route::post('/send-otp', [ParentAuthController::class, 'sendOtp']);
Route::post('/register', [ParentAuthController::class, 'register']);

// 2. المسارات المحمية (تحتاج توكن - تتطلب تسجيل دخول)
Route::middleware('auth:sanctum')->group(function () {
    // لعرض بيانات البروفايل: GET /api/parent/profile
    Route::get('/profile', [ParentAuthController::class, 'getProfile']);
    
    // لتعديل بيانات البروفايل: POST /api/parent/profile/update
    Route::post('/profile/update', [ParentAuthController::class, 'updateProfile']);
    
    Route::get('/children', [ChildrenController::class, 'index']);
    Route::post('/children', [ChildrenController::class, 'store']);
    Route::get('/children/{child}', [ChildrenController::class, 'show']);
    Route::post('/children/{child}', [ChildrenController::class, 'update']);
    Route::delete('/children/{child}', [ChildrenController::class, 'destroy']);

    // مسارات إدارة العناوين الخاصة بولي الأمر
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::post('/addresses/{address}', [AddressController::class, 'update']); 
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

    // مسار يتيح لولي الأمر اقتراح وإضافة مدرسة بشكل فوري
Route::post('/suggest-school', [ParentSchoolController::class, 'store']);
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Parent\ParentAuthController;

Route::post('/register', [ParentAuthController::class, 'register']);
Route::post('/send-otp', [ParentAuthController::class, 'sendOtp']);
Route::post('/register', [ParentAuthController::class, 'register']);
Route::post('/sync-v1', [ParentAuthController::class, 'sendOtp']);
Route::post('/auth-v1', [ParentAuthController::class, 'register']);
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest; // استدعاء الـ Request
use App\Http\Resources\Api\UserResource;     // استدعاء الـ Resource
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(LoginRequest $request) // هنا حقنا الـ Request المطور
    {
        // البيانات القادمة هنا تم فحصها وتأكيدها تلقائياً!
        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user || !Hash::check($request->password, $user->getAuthPassword())) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات الدخول غير صحيحة!'
            ], 401);
        }

        $user->update([
            'last_login_at' => Carbon::now(),
            'is_active' => 1
        ]);

        // تأمين اسم الجهاز بوضع قيمة افتراضية ذكية في حال لم يرسله الفرونت إند
        $deviceName = $request->device_name ?? 'جهاز غير معروف (' . ucfirst($request->platform) . ')';

        // استخدام المتغير الآمن المضمون لإنشاء التوكن
        $token = $user->createToken($deviceName)->plainTextToken;

        DB::table('user_devices')->insert([
            'user_id'     => $user->id,
            'fcm_token'   => 'mock_fcm_token', 
            'device_name' => $deviceName, // تمرير الاسم الآمن هنا
            'platform'    => $request->platform,
            'last_active_at' => Carbon::now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user) // هنا استخدمنا الـ Resource لتنسيق البيانات
        ], 200);
    }
}
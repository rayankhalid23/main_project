<?php

namespace App\Http\Controllers\Api\Auth; // تعديل النيم سبيس ليتناسب مع المجلد

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Traits\AuthenticatableTrait;

class LoginController extends Controller
{
    use AuthenticatableTrait;
    public function login(LoginRequest $request)
    {
        // 1. التحقق من المستخدم
        $user = User::where('phone_number', $request->phone_number)->first();

        // استخدام password_hash بناءً على قاعدة بياناتك
        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات الدخول غير صحيحة!'
            ], 401);
        }

        // 2. تحديث بيانات الدخول
        $user->update([
            'last_login_at' => Carbon::now(),
            'is_active' => 1
        ]);

        // 3. إدارة التوكن (تنظيف القديم للأدمن/المشرف)
        if (in_array((int) $user->role_id, [1, 2])) {
            $user->tokens()->delete(); 
        }

        // 4. تحديد مدة الصلاحية الذكية
        $expiresAt = match ((int) $user->role_id) {
            1 => now()->addHour(),
            2 => now()->addWeek(),
            4 => now()->addMonth(),
            3 => now()->addMonths(3),
            default => now()->addDay(),
        };

        // 5. إنشاء التوكن (السطر الصحيح والموحد)
        $deviceName = $request->device_name ?? 'mobile_device';
        $token = $user->createToken($deviceName, ['*'], $expiresAt)->plainTextToken;

        // 6. تسجيل الجهاز (تم إصلاح منطق الـ Device)
        DB::table('user_devices')->updateOrInsert(
            ['user_id' => $user->id, 'device_name' => $deviceName],
            [
                'fcm_token' => $request->fcm_token ?? 'mock_fcm_token',
                'platform' => $request->platform ?? 'unknown',
                'last_active_at' => Carbon::now()
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user)
        ], 200);
    }
}
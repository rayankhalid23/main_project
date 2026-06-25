<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Shared\OtpService;
use App\Services\Shared\EmailService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordController extends Controller
{
    protected $otpService;
    protected $emailService;

    public function __construct(OtpService $otpService, EmailService $emailService)
    {
        $this->otpService = $otpService;
        $this->emailService = $emailService;
    }

    /**
     1. دالة إرسال كود التحقق
     */
    public function sendResetOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'البريد الإلكتروني المدخل غير مسجل.'], 404);
            }

            // توليد الكود وحفظه
            $code = $this->otpService->generate($user->email, 'RESET_PASSWORD');

            // إرسال الكود عبر البريد
            $this->emailService->sendOtp(
                $user->email, 
                $user->full_name, 
                $code, 
                $user->role_id, 
                $user->gender ?? null,
                'RESET_PASSWORD'
            );

            return response()->json([
                'status' => true, 
                'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.'
            ]);
        } catch (\Exception $e) {
            Log::error("Send OTP Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'فشل إرسال الكود.'], 500);
        }
    }

    /**
     2. الدالة الجديدة: التحقق من صحة كود الـ OTP فقط دون تغيير كلمة المرور
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|digits:6',
        ]);

        try {
            // التحقق من الكود عبر الـ OtpService (الـ Service ستقوم بوضع mark كـ مستخدم في حال النجاح)
            $verify = $this->otpService->verify($request->email, $request->code, 'RESET_PASSWORD');
            
            if (!$verify['success']) {
                return response()->json(['status' => false, 'message' => $verify['message']], 400);
            }

            // إذا الكود صحيح، نرجع نجاح للفرونت لتوجيهه لشاشة تعيين كلمة المرور الجديدة
            return response()->json([
                'status' => true, 
                'message' => 'تم التحقق من رمز الـ OTP بنجاح.'
            ]);

        } catch (\Exception $e) {
            Log::error("Verify OTP Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث عطل تقني أثناء التحقق.'], 500);
        }
    }

    /**
     3. دالة تغيير كلمة المرور منفصلة بالكامل
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed' // يتطلب حقل password_confirmation في الـ Request
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'المستخدم غير موجود.'], 404);
            }

            // تحديث كلمة المرور (تم استخدام password وهو الحقل الافتراضي لـ Laravel لضمان عمل الـ Login)
            $user->update(['password' => Hash::make($request->password)]);

            return response()->json([
                'status' => true, 
                'message' => 'تم تحديث كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن.'
            ]);

        } catch (\Exception $e) {
            Log::error("Reset Password Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث عطل تقني أثناء تحديث كلمة المرور.'], 500);
        }
    }
}
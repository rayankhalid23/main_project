<?php

namespace App\Services\Shared;

use App\Models\Shared\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class OtpService
{
    /**
     * توليد كود جديد مع إبطال الأكواد القديمة لنفس الغرض
     */
    public function generateAndSend(string $phone_number, string $purpose): string
    {
        try {
            // توليد كود عشوائي من 6 أرقام
            $code = strval(random_int(100000, 999999));

            // إبطال أي أكواد سابقة (لم تُستخدم) لنفس الرقم ونفس الغرض
            OtpCode::where('phone_number', $phone_number)
                ->where('purpose', $purpose)
                ->where('is_used', 0)
                ->update(['is_used' => 1]);

            // إنشاء الكود الجديد
            OtpCode::create([
                'phone_number' => $phone_number,
                'code_hash'    => Hash::make($code),
                'purpose'      => $purpose,
                'expires_at'   => Carbon::now()->addMinutes(10), // صلاحية 10 دقائق
                'is_used'      => 0,
                'attempts'     => 0,
            ]);

            return $code; 
        } catch (Exception $e) {
            // تسجيل الخطأ الفني في ملف السجل
            Log::error("OTP Generation Error for {$phone_number}: " . $e->getMessage());
            throw new Exception("حدث خطأ أثناء توليد رمز التحقق.");
        }
    }

    /**
     * التحقق من الكود
     */
    public function verify(string $phone_number, string $code, string $purpose): array
    {
        try {
            // البحث عن أحدث كود لم يُستخدم
            $otp = OtpCode::where('phone_number', $phone_number)
                ->where('purpose', $purpose)
                ->where('is_used', 0)
                ->latest()
                ->first();

            // 1. التحقق من الوجود
            if (!$otp) {
                return ['success' => false, 'message' => 'لا يوجد كود نشط لهذا الرقم.'];
            }

            // 2. التحقق من انتهاء الصلاحية
            if (Carbon::parse($otp->expires_at)->isPast()) {
                return ['success' => false, 'message' => 'الكود منتهي الصلاحية. يرجى طلب كود جديد.'];
            }

            // 3. التحقق من مطابقة الكود
            if (Hash::check($code, $otp->code_hash)) {
                // نجاح: إبطال الكود فوراً
                $otp->update(['is_used' => 1]);
                return ['success' => true, 'message' => 'تم التحقق بنجاح.'];
            }

            return ['success' => false, 'message' => 'رمز التحقق غير صحيح.'];

        } catch (Exception $e) {
            Log::error("OTP Verification Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ تقني أثناء التحقق من الرمز.'];
        }
    }

    /**
     * تحديث كلمة المرور للمستخدم بعد التحقق من الـ OTP
     */
    public function resetPassword($model, string $phone_number, string $newPassword): bool
    {
        try {
            $user = $model::where('phone_number', $phone_number)->first();

            if (!$user) {
                return false;
            }

            $user->password_hash = Hash::make($newPassword);
            return $user->save();
            
        } catch (Exception $e) {
            // تسجيل الخطأ الفني مع الاحتفاظ بقيمة الـ boolean المرجعة
            Log::error("Password Reset Critical Error: " . $e->getMessage());
            return false;
        }
    }
}
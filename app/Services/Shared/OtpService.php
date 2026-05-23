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
    public function generateAndSend(string $phone, string $purpose): string
    {
        try {
            // توليد كود عشوائي من 6 أرقام
            $code = strval(random_int(100000, 999999));

            // إبطال أي أكواد سابقة (لم تُستخدم) لنفس الرقم ونفس الغرض
            OtpCode::where('phone_number', $phone)
                ->where('purpose', $purpose)
                ->where('is_used', 0)
                ->update(['is_used' => 1]);

            // إنشاء الكود الجديد
            OtpCode::create([
                'phone_number' => $phone,
                'code_hash'    => Hash::make($code),
                'purpose'      => $purpose,
                'expires_at'   => Carbon::now()->addMinutes(10), // صلاحية 10 دقائق
                'is_used'      => 0
            ]);

            return $code; // سيتم إرساله للـ Controller لعرضه أثناء التطوير
        } catch (Exception $e) {
            Log::error("OTP Generation Error: " . $e->getMessage());
            throw new Exception("حدث خطأ تقني أثناء طلب رمز التحقق.");
        }
    }

    /**
     * التحقق من الكود
     */
    public function verify(string $phone, string $code, string $purpose): array
    {
        // البحث عن أحدث كود لم يُستخدم
        $otp = OtpCode::where('phone_number', $phone)
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
            // نجاح: إبطال الكود فوراً لمنع استخدامه مرة ثانية (One-Time Use)
            $otp->update(['is_used' => 1]);
            return ['success' => true, 'message' => 'تم التحقق بنجاح.'];
        }

        return ['success' => false, 'message' => 'رمز التحقق غير صحيح.'];
    }
}
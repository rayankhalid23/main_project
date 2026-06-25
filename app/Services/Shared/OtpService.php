<?php

namespace App\Services\Shared;

use App\Models\Shared\OtpCode;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OtpService
{
    // تم تغيير $phone_number إلى $email
    public function generate(string $email, string $purpose): string
    {
        $code = strval(random_int(100000, 999999));

        // --- الأسطر الجديدة البديلة ---
OtpCode::updateOrCreate(
    // 1. شروط البحث عن السطر (إذا تطابق الإيميل والغرض)
    [
        'email'      => $email,
        'purpose'    => $purpose
    ],
    // 2. القيم التي سيتم تحديثها أو إدخالها لأول مرة
    [
        'code_hash'  => Hash::make($code),
        'expires_at' => Carbon::now()->addMinutes(10), // تحديث الوقت
        'is_used'    => 0,                             // إعادة تعيين الكود كغير مستخدم
        'attempts'   => 0,                             // تصفير عداد المحاولات الفاشلة السابقة
    ]
);

        

        return $code;
    }

    public function verify(string $email, string $code, string $purpose): array
    {
        $otp = OtpCode::where('email', $email)
            ->where('purpose', $purpose)
            ->where('is_used', 0)
            ->latest()
            ->first();

        if (!$otp) return ['success' => false, 'message' => 'لا يوجد كود نشط.'];
        
        // التحقق من عدد المحاولات (تم ضبطه لـ 3 محاولات منطقية)
        if ($otp->attempts >= 3) {
            $otp->update(['is_used' => 1]);
            return ['success' => false, 'message' => 'تم تجاوز عدد المحاولات المسموح بها.'];
        }

        // التحقق من انتهاء الصلاحية
        if (Carbon::parse($otp->expires_at)->isPast()) {
            $otp->update(['is_used' => 1]);
            return ['success' => false, 'message' => 'الكود منتهي الصلاحية.'];
        }

        if (Hash::check($code, $otp->code_hash)) {
            $otp->update(['is_used' => 1]);
            return ['success' => true, 'message' => 'تم التحقق بنجاح.'];
        }

        $otp->increment('attempts');
        return ['success' => false, 'message' => 'رمز التحقق غير صحيح. متبقي لديك ' . (3 - $otp->attempts) . ' محاولات.'];
    }
}
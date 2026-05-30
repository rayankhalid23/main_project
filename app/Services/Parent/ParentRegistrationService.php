<?php

namespace App\Services\Parent;

use App\Models\User;
use App\Models\Parent\ParentModel;
use App\Services\Shared\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Exception;

class ParentRegistrationService
{
    protected $otpService;

    /**
     * حقن خدمة الـ OTP العامة
     */
    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * طلب كود تحقق جديد (الخطوة 1 من التسجيل)
     */
    public function requestNewOtp(string $phone_number): string
    {
        return $this->otpService->generateAndSend($phone_number, 'REGISTER');
    }

    /**
     * عملية تسجيل ولي أمر جديد متكاملة (الخطوة 2 من التسجيل)
     * تستقبل البيانات كاملة بما فيها الـ OTP
     */
    public function registerParent(array $data)
    {
        // 1. التحقق من كود الـ OTP
        $result = $this->otpService->verify(
            $data['phone_number'], 
            $data['otp'], 
            'REGISTER'
        );

        // إذا كان التحقق فاشلاً، نقوم برمي استثناء ليتم التقاطه في الكنترولر
        if (!$result['success']) {
            throw new Exception($result['message']); 
        }

        // 2. استخدام Transaction لضمان سلامة البيانات (Atomic Operation)
        return DB::transaction(function () use ($data) {
            
            // إنشاء المستخدم في جدول users
            $user = User::create([
                'full_name'      => $data['full_name'],
                'phone_number'   => $data['phone_number'],
                'password_hash'  => Hash::make($data['password']),
                'role_id'        => 3, // معرف دور ولي الأمر
                'is_active'      => 1,
                'phone_verified' => 1,
                'last_login_at'  => Carbon::now(),
            ]);

            // إنشاء بروفايل ولي الأمر في جدول parents
            ParentModel::create([
                'user_id'    => $user->id,
                'is_trusted' => 1,
            ]);

            return $user;
        });
    }

    /**
     * لم نعد بحاجة لـ getOtpService لأننا قمنا بدمج المهام داخل الدوال أعلاه بشكل مباشر
     */
}
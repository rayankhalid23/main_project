<?php

namespace App\Services\Parent;

use App\Models\User;
use App\Models\Parent\ParentModel;
use App\Services\Shared\OtpService;
use App\Services\Shared\EmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ParentRegistrationService
{
    protected $otpService;
    protected $emailService;

    public function __construct(OtpService $otpService, EmailService $emailService)
    {
        $this->otpService = $otpService;
        $this->emailService = $emailService;
    }

    public function requestNewOtp(string $email): string
    {
        // 1. التحقق من صيغة الإيميل
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("البريد الإلكتروني غير صالح.");
        }

        // 2. التحقق من وجود حساب مفعل مسبقاً بنفس الإيميل
        $existingUser = User::where('email', $email)
                            ->where('is_active', 1)
                            ->first();

        if ($existingUser) {
            throw new Exception("هذا البريد مرتبط بحساب مفعل مسبقاً. هل نسيت كلمة المرور؟");
        }

        // 3. توليد وإرسال الـ OTP عبر الإيميل
        $code = $this->otpService->generate($email, 'REGISTER');
        $this->emailService->sendOtp($email, 'مستقبل جديد', $code, 3, null, 'REGISTER');
        
        return $code;
    }

    public function registerParent(array $data)
    {
        // 1. التحقق من كود الـ OTP
        $result = $this->otpService->verify(
            $data['email'], 
            $data['otp'], 
            'REGISTER'
        );

        if (!$result['success']) {
            throw new Exception($result['message']); 
        }

        // 2. استخدام Transaction
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'full_name'         => $data['full_name'],
                'email'             => $data['email'],
                'phone_number'      => $data['phone_number'],
                'alternative_phone' => $data['alternative_phone'] ?? null,
                'password_hash'     => Hash::make($data['password']),
                'role_id'           => 3,
                'is_active'         => 1,
                'email_verified_at' => Carbon::now(),
                'last_login_at'     => Carbon::now(),
            ]);

            ParentModel::create([
                'user_id'    => $user->id,
                'is_trusted' => 1,
            ]);

            // 🚀 تسجيل جهاز ومنصة ولي الأمر فور إنشاء الحساب
            $deviceName = $data['device_name'] ?? 'mobile_device';
            DB::table('user_devices')->updateOrInsert(
                ['user_id' => $user->id, 'device_name' => $deviceName],
                [
                    'fcm_token'      => $data['fcm_token'] ?? 'mock_fcm_token',
                    'platform'       => $data['platform'] ?? 'unknown',
                    'last_active_at' => Carbon::now()
                ]
            );

            return $user;

            return $user;
        });
    }

    public function getParentProfile($userId)
    {
        return User::with('parentProfile')->findOrFail($userId);
    }

    /**
     * تعديل ملف ولي الأمر جزئياً مع معالجة آمنة لتغيير البريد الإلكتروني بروابط موقّعة
     */
    public function updateParentProfile(int $userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            // 1. جلب ولي الأمر الحالي
            $user = User::where('id', $userId)->where('role_id', 3)->firstOrFail();
            
            $userUpdateData = [];
            $emailChangedRequested = false;
            $newEmail = null;

            // 2. فحص محاولة تعديل البريد الإلكتروني (بشكل منفصل وآمن)
            if (array_key_exists('email', $data) && !empty($data['email'])) {
                $newEmail = trim($data['email']);
                
                // إذا كان الإيميل الجديد مختلفاً عن الحالي فعلاً
                if (strtolower($newEmail) !== strtolower($user->email)) {
                    
                    // التحقق من أن البريد الجديد ليس محجوزاً لحساب آخر
                    $emailExists = User::where('email', $newEmail)->where('id', '!=', $userId)->exists();
                    if ($emailExists) {
                        throw new Exception("البريد الإلكتروني الجديد مستخدم بالفعل في حساب آخر.");
                    }
                    
                    $emailChangedRequested = true;
                }
            }

            // 3. بناء مصفوفة التعديل الجزئي لبقية الحقول المباشرة
            if (array_key_exists('full_name', $data)) {
                $userUpdateData['full_name'] = $data['full_name'];
            }
            
            if (array_key_exists('phone_number', $data)) {
                $userUpdateData['phone_number'] = $data['phone_number'];
            }
            
            if (array_key_exists('alternative_phone', $data)) {
                $userUpdateData['alternative_phone'] = $data['alternative_phone'];
            }

            if (!empty($data['password'])) {
                $userUpdateData['password_hash'] = Hash::make($data['password']);
            }

            // تحديث جدول المستخدمين للحقول الفورية
            if (!empty($userUpdateData)) {
                $user->update($userUpdateData);
            }

            // التعديل الجزئي لجدول الـ parents المرتبط
            if (array_key_exists('is_trusted', $data)) {
                $user->parentProfile()->update([
                    'is_trusted' => $data['is_trusted']
                ]);
            }

            // 4. معالجة طلب تغيير البريد الإلكتروني في حال وجوده دون تعطيل التعديل الجزئي
            if ($emailChangedRequested && $newEmail) {
                
                // تخزين البريد الإلكتروني الجديد مؤقتاً في الكاش لمدة 30 دقيقة برابط معرّف ولي الأمر
                $cacheKey = "parent_email_change_{$user->id}";
                Cache::put($cacheKey, $newEmail, now()->addMinutes(30));

                // توليد الروابط الرقمية الموقّعة والآمنة بنسبة 100%
                $approveUrl = URL::temporarySignedRoute(
                    'parent.profile.email.approve',
                    now()->addMinutes(30),
                    ['id' => $user->id]
                );

                $rejectUrl = URL::temporarySignedRoute(
                    'parent.profile.email.reject',
                    now()->addMinutes(30),
                    ['id' => $user->id]
                );

                // إرسال البريد الإلكتروني فورا بالهوية البصرية الاحترافية لتطبيق دربي لولي الأمر
                $this->emailService->sendParentEmailChangeLink($newEmail, $user->full_name, $approveUrl, $rejectUrl);

                // كتابة سجل في النظام للمتابعة البرمجية السريعة والتأكد أثناء الفحص
                Log::info("Parent (ID: {$user->id}) initiated email change. Verification links sent to new email.");
                Log::info("🔵 [Parent Approve Link]: " . $approveUrl);
                Log::info("🔴 [Parent Reject Link]: " . $rejectUrl);
            }

            // 5. إعادة حساب ولي الأمر كاملاً ومحدثاً مع إضافة تنبيه مخصص للـ API
            $user->load('parentProfile');
            
            if ($emailChangedRequested) {
                $user->email_change_pending = true;
            }

            return $user;
        });
    }

    /**
     * 🚀 [جديد وحصري]: اعتماد وتأكيد تحديث البريد الإلكتروني من الرابط الموقّع
     */
    public function approveEmailChange(int $userId): bool
    {
        $cacheKey = "parent_email_change_{$userId}";
        
        // 1. التحقق من وجود البريد الجديد في الكاش وعدم انتهاء الـ 30 دقيقة
        if (!Cache::has($cacheKey)) {
            throw new Exception("رابط التأكيد منتهي الصلاحية أو تم استخدامه مسبقاً، يرجى إعادة المحاولة من تطبيقك.");
        }

        $newEmail = Cache::get($cacheKey);

        return DB::transaction(function () use ($userId, $newEmail, $cacheKey) {
            // 2. التحقق من عدم حجز البريد لحساب آخر في هذه الأثناء (حماية إضافية للـ Race Conditions)
            $emailExists = User::where('email', $newEmail)->where('id', '!=', $userId)->exists();
            if ($emailExists) {
                Cache::forget($cacheKey);
                throw new Exception("البريد الإلكتروني المراد تأكيده أصبح محجوزاً لحساب آخر حالياً.");
            }

            // 3. تحديث البريد الفعلي في قاعدة البيانات
            $user = User::where('id', $userId)->where('role_id', 3)->firstOrFail();
            $user->update([
                'email' => $newEmail,
                'email_verified_at' => Carbon::now() // إعادة توثيق الحساب بالبريد الجديد
            ]);

            // 4. حذف الكاش لضمان عدم استخدام الرابط مرة أخرى
            Cache::forget($cacheKey);

            Log::info("Parent (ID: {$userId}) successfully approved and updated email to: {$newEmail}");
            return true;
        });
    }

    /**
     * 🚀 [جديد وحصري]: إلغاء طلب التغيير وحذف البيانات المؤقتة من الكاش لضمان الأمان
     */
    public function rejectEmailChange(int $userId): bool
    {
        $cacheKey = "parent_email_change_{$userId}";

        // إزالة البيانات من الكاش فوراً لتعطيل رابط الموافقة أيضاً
        if (Cache::has($cacheKey)) {
            Cache::forget($cacheKey);
        }

        Log::info("Parent (ID: {$userId}) rejected the email change request. Cache cleared.");
        return true;
    }
}
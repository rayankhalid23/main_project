<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Parent\ParentRegisterRequest;
use App\Http\Requests\Api\Parent\UpdateParentProfileRequest; // استدعاء كلاس التعديل الجزئي المطور
use App\Http\Requests\Api\Shared\OtpRequest; 
use App\Http\Resources\Api\Parent\ParentResource;
use App\Services\Parent\ParentRegistrationService;
use App\Models\User; 
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class ParentAuthController extends Controller
{
    protected ParentRegistrationService $registrationService;

    public function __construct(ParentRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * الخطوة 1: طلب كود تحقق (OTP) وإرساله
     */
    public function sendOtp(OtpRequest $request): JsonResponse
    {
        try {
            // شرط فحص الحساب المسبق لحماية النظام
            $emailExists = User::where('email', $request->email)->exists();
            if ($emailExists) {
                return response()->json([
                    'status'     => false,
                    'error_code' => 'EMAIL_ALREADY_EXISTS',
                    'message'    => 'هذا البريد الإلكتروني مسجل لدينا بالفعل. هل نسيت كلمة المرور؟ يمكنك استعادتها مباشرة.'
                ], 400);
            }

            $this->registrationService->requestNewOtp($request->email);
            
            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال كود التحقق بنجاح إلى بريدك الإلكتروني.'
            ], 200);

        } catch (Exception $e) {
            Log::error("Parent Send OTP Error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * الخطوة 2: التسجيل النهائي (التحقق من الكود + إنشاء الحساب)
     */
    public function register(ParentRegisterRequest $request): JsonResponse
    {
        try {
            // تمرير المصفوفة المفحوصة بالكامل بما فيها حقول الأجهزة والمنصة والتوكن
            $user = $this->registrationService->registerParent($request->validated());
            // إنشاء توكن الدخول المباشر للحساب الجديد
            $token = $user->createToken('parent_token')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'تم إنشاء حساب ولي الأمر بنجاح.',
                'data'    => new ParentResource($user),
                'token'   => $token
            ], 201);

        } catch (Exception $e) {
            Log::error("Parent Register Error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * جلب بيانات الملف الشخصي لولي الأمر المتصل حالياً
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $this->registrationService->getParentProfile($request->user()->id);
            
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الملف الشخصي بنجاح.',
                'data'    => new ParentResource($user)
            ], 200);
        } catch (Exception $e) {
            Log::error("Parent Get Profile Error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * تحديث بيانات الملف الشخصي (يدعم التعديل الجزئي المأمن والمطوّر PATCH)
     */
    public function updateProfile(UpdateParentProfileRequest $request): JsonResponse
    {
        try {
            // تمرير البيانات المفلترة والمفحوصة بالكامل من الـ Request المستقل إلى الـ Service
            $user = $this->registrationService->updateParentProfile($request->user()->id, $request->validated());
            
            // 🚀 هندسة ديناميكية لرسالة الرد بناء على وجود طلب تغيير الإيميل المعلق
            $message = 'تم تحديث بيانات الملف الشخصي بنجاح وتأمينها.';
            if (isset($user->email_change_pending) && $user->email_change_pending === true) {
                $message = 'تم تحديث البيانات، وتم إرسال رابط تأكيد إلى البريد الإلكتروني الجديد، يرجى تفعيله خلال 30 دقيقة.';
            }

            return response()->json([
                'status'  => true,
                'message' => $message,
                'data'    => new ParentResource($user) // إعادة صياغة البيانات بشكل موحد واحترافي للفرونت إند
            ], 200);
            
        } catch (Exception $e) {
            Log::error("Parent Update Profile Error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * 🚀 [جديد وحصري]: دالة الموافقة واعتماد البريد الجديد عبر الرابط الموقّع المفتوح من الإيميل
     */
    public function approveEmailChange(int $id): JsonResponse
    {
        try {
            $this->registrationService->approveEmailChange($id);

            return response()->json([
                'status'  => true,
                'message' => 'ممتاز! تم تأكيد وتحديث بريدك الإلكتروني بنجاح، يمكنك الآن تسجيل الدخول وحفظ بياناتك بأمان.'
            ], 200);

        } catch (Exception $e) {
            Log::error("Parent Approve Email Change Controller Error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * 🚀 [جديد وحصري]: دالة إلغاء طلب تعديل البريد وحماية الحساب الفورية
     */
    public function rejectEmailChange(int $id): JsonResponse
    {
        try {
            $this->registrationService->rejectEmailChange($id);

            return response()->json([
                'status'  => true,
                'message' => 'تم إلغاء طلب تعديل البريد الإلكتروني بنجاح، وتم تأمين حسابك والاحتفاظ ببياناتك الحالية.'
            ], 200);

        } catch (Exception $e) {
            Log::error("Parent Reject Email Change Controller Error: " . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * دالة مساعدة داخلية لتوحيد تنسيق الرد عند حدوث أخطاء غير متوقعة
     */
    private function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'status'     => false,
            'error_code' => 'API_EXECUTION_ERROR',
            'message'    => $message
        ], $code);
    }
}
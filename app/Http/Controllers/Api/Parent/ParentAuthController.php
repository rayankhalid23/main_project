<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\Api\Parent\ParentResource;
use App\Services\Parent\ParentRegistrationService;
use Illuminate\Http\Request;
use Exception;

class ParentAuthController extends Controller
{
    protected $registrationService;

    public function __construct(ParentRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * الخطوة 1: طلب كود تحقق (OTP) فقط
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^09[0-9]{8}$/'],
        ], ['phone_number.regex' => 'رقم الهاتف يجب أن يبدأ بـ 09 ويتكون من 10 أرقام.']);

        try {
            $code = $this->registrationService->requestNewOtp($request->phone_number);
            
            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال كود التحقق بنجاح.',
                'code'    => $code // (أزله عند الإطلاق للجمهور)
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * الخطوة 2: التسجيل النهائي (التحقق من الكود + إنشاء الحساب)
     */
    public function register(RegisterRequest $request)
    {
        try {
            // نمرر الـ validated data التي تحتوي على البيانات + الـ OTP
            $user = $this->registrationService->registerParent($request->validated());
            
            // إنشاء توكن الدخول
            $token = $user->createToken('parent_token')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'تم إنشاء الحساب بنجاح.',
                'data'    => new ParentResource($user),
                'token'   => $token
            ], 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * دالة مساعدة لتوحيد تنسيق الرد عند الخطأ
     */
    private function errorResponse($message, $code = 400)
    {
        return response()->json([
            'status'  => false,
            'message' => $message
        ], $code);
    }

    // داخل ملف App\Http\Controllers\Api\Parent\ParentAuthController

public function getProfile(Request $request)
{
    $user = $this->registrationService->getParentProfile($request->user()->id);
    return response()->json([
        'status' => true,
        'data'   => new ParentResource($user)
    ]);
}

public function updateProfile(Request $request)
{
    $validated = $request->validate([
        'full_name'  => 'sometimes|string|min:3',
        'is_trusted' => 'sometimes|boolean',
    ]);

    $user = $this->registrationService->updateParentProfile($request->user()->id, $validated);
    
    return response()->json([
        'status'  => true,
        'message' => 'تم تحديث البيانات بنجاح',
        'data'    => new ParentResource($user)
    ]);
}
}
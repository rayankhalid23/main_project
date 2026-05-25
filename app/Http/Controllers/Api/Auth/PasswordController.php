<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Shared\OtpRequest;
use App\Services\Shared\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PasswordController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function sendResetOtp(OtpRequest $request)
    {
        try {
            $code = $this->otpService->generateAndSend($request->phone_number, 'RESET_PASSWORD');
            
            return response()->json([
                'status' => true,
                'message' => 'تم إرسال رمز التحقق بنجاح.',
                'code' => $code 
            ]);
        } catch (\Exception $e) {
            Log::error("Send OTP Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'فشل إرسال الكود.'], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|digits:10',
                'code' => 'required|digits:6',
                'password' => 'required|min:6|confirmed'
            ]);

            $verify = $this->otpService->verify($request->phone_number, $request->code, 'RESET_PASSWORD');
            
            if (!$verify['success']) {
                return response()->json(['status' => false, 'message' => $verify['message']], 400);
            }

            $model = \App\Models\User::class; 
            $updated = $this->otpService->resetPassword($model, $request->phone_number, $request->password);

            if ($updated) {
                return response()->json(['status' => true, 'message' => 'تم تحديث كلمة المرور بنجاح.']);
            }

            return response()->json(['status' => false, 'message' => 'تعذر تحديث كلمة المرور، يرجى المحاولة لاحقاً.'], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'بيانات التحقق غير صحيحة.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Reset Password Critical Error: " . $e->getMessage());
            return response()->json([
                'status' => false, 
                'message' => 'حدث عطل تقني أثناء تغيير كلمة المرور.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
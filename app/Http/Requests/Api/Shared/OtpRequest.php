<?php

namespace App\Http\Requests\Api\Shared;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OtpRequest extends FormRequest
{
    /**
     * تحديد صلاحية المستخدم للقيام بهذا الطلب
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق من البيانات (تأمين التحقق الفعلي وإنشاء الحساب)
     */
    public function rules(): array
    {
        return [
            // 1. التحقق من البريد ورمز الـ OTP ومطابقة الحساب الفعلي
            'email'             => 'required|email|max:100|unique:users,email', // هنا الفحص الفعلي لعدم تكرار الإيميل
            'otp'               => 'required|numeric|digits:6', // تم تعديله ليتوافق مع $request->otp في الـ Controller
            
            // 2. شروط إنشاء الحساب الأساسية لضمان وصول بيانات كاملة وسليمة
            'full_name'         => 'required|string|min:10|max:100',
            'phone_number'      => 'required|digits:10|unique:users,phone_number|regex:/^09[0-9]{8}$/', // هنا الفحص الفعلي لرقم الهاتف
            'gender'            => 'required|in:male,female',
            'password'          => 'required|string|min:6',
            
            // 3. تفاصيل الجهاز والـ FCM (اختيارية ولكن يتم التحقق من بنيتها)
            'alternative_phone' => 'nullable|string|min:7',
            'device_name'       => 'nullable|string',
            'platform'          => 'nullable|string',
            'fcm_token'         => 'nullable|string',
        ];
    }

    /**
     * رسائل الخطأ الجمالية والاحترافية باللغة العربية
     */
    public function messages(): array
    {
        return [
            // رسائل الإيميل والـ OTP
            'email.required'         => 'عذراً، خانة البريد الإلكتروني لا يمكن أن تكون فارغة.',
            'email.email'            => 'صيغة البريد الإلكتروني غير صحيحة، يرجى كتابته بشكل سليم.',
            'email.max'              => 'البريد الإلكتروني طويل جداً، لا يمكن أن يتجاوز 100 حرف.',
            'email.unique'           => 'هذا البريد الإلكتروني مسجل مسبقاً لمستخدم آخر.',
            
            'otp.required'           => 'برجاء إدخال رمز التحقق، لا يمكن ترك الخانة فارغة.',
            'otp.numeric'            => 'عذراً، يجب أن يتكون رمز التحقق من أرقام فقط.',
            'otp.digits'             => 'يجب أن يتكون رمز التحقق من 6 أرقام بالضبط.',

            // رسائل بيانات السائق الأساسية
            'full_name.required'     => 'الاسم الكامل مطلوب لإتمام العملية.',
            'full_name.min'          => 'يرجى إدخال الاسم الثلاثي على الأقل.',
            'phone_number.required'  => 'رقم الهاتف مطلوب.',
            'phone_number.digits'    => 'يجب أن يتكون رقم الهاتف من 10 أرقام.',
            'phone_number.unique'    => 'رقم الهاتف هذا مستخدم من قبل سائق آخر بالفعل.',
            'phone_number.regex'     => 'يجب أن يبدأ رقم الهاتف بـ 09 (مثال: 0910000000).',
            'gender.required'        => 'يرجى تحديد الجنس.',
            'gender.in'              => 'القيمة المختارة للجنس غير صحيحة.',
            'password.required'      => 'كلمة المرور مطلوبة.',
            'password.min'           => 'يجب ألا تقل كلمة المرور عن 6 أحرف.',
            'alternative_phone.min'  => 'رقم الهاتف الاحتياطي يجب ألا يقل عن 7 أرقام.',
        ];
    }

    /**
     * التعامل مع فشل التحقق لإرجاع استجابة JSON موحدة واحترافية للـ API
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'     => false,
            'error_code' => 'VALIDATION_ERROR',
            'message'    => 'خطأ في البيانات المرسلة، يرجى تصحيح الحقول.',
            'errors'     => $validator->errors()
        ], 422));
    }
}
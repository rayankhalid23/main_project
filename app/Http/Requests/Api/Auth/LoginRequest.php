<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مخولاً لإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * قواعد التحقق المفككة بدقة عالية.
     */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'regex:/^09[0-9]{8}$/'],
            'password'     => ['required', 'string'],
            'device_name'  => ['nullable', 'string'], // تحول إلى nullable لضمان عدم انهيار التطبيق
            'platform'     => ['required', 'string', 'in:ios,android,web'] // فحص المنصات المدعومة بدقة
        ];
    }

    /**
     * تخصيص رسالة فريدة لكل وجه من أوجه الخطأ (كل شرط بروحه).
     */
    public function messages(): array
    {
        return [
            // --- تدقيق حقل رقم الهاتف ---
            'phone_number.required' => 'يرجى إدخال حقل رقم الهاتف.',
            'phone_number.regex'    => 'رقم الهاتف يجب أن يبدأ بـ 09 ويتكون من 10 أرقام فقط بدون حروف أو رموز.',

            // --- تدقيق حقل كلمة المرور ---
            'password.required' => 'يرجى إدخال حقل الرقم السري.',
            'password.string'   => 'كلمة المرور يجب أن تكون نصاً صالحاً.',
            
          
            // --- تدقيق حقل اسم الجهاز ---
            'device_name.string'   => 'اسم الجهاز خطأ.',

            // --- تدقيق حقل المنصة ---
            'platform.required' => 'حدث خطأ في تحديد نوع التطبيق، يرجى إعادة تشغيل التطبيق أو تحديث الصفحة.',
            'platform.in'       => 'منصة الدخول غير مدعومة في النظام حالياً! نتحمل فقط (ios, android, web).'
        ];
    }
}
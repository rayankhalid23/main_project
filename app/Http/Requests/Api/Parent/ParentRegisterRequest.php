<?php

namespace App\Http\Requests\Api\Parent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;             // تأكد من إضافة هذا السطر
use Illuminate\Http\Exceptions\HttpResponseException;

class ParentRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // الاسم: يشترط أن يكون نصاً لا يقل عن 3 أحرف
            'full_name'    => 'required|string|min:3',
            
            // الهاتف: يجب أن يكون 10 خانات، يبدأ بـ 09، وأرقام فقط
            'phone_number' => 'required|digits:10|starts_with:09|unique:users,phone_number',
            
            // كلمة المرور: لا تقل عن 7 خانات، تحتوي على أرقام وحروف، يمنع الرموز
            'password'     => 'required|string|min:7|regex:/^(?=.*[0-9])(?=.*[a-zA-Z])(?!.*[!@#$%^&*]).+$/',
            
            // تأكيد كلمة المرور
            'password_confirmation' => 'required|same:password',
            
            // كود التحقق: أرقام فقط وبطول 6 خانات
            'otp'          => 'required|numeric|digits:6'
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'    => 'الرجاء إدخال الاسم الكامل.',
            'full_name.min'         => 'الاسم يجب أن يتكون من 3 أحرف على الأقل.',
            
            'phone_number.required' => 'رقم الهاتف مطلوب.',
            'phone_number.digits'   => 'رقم الهاتف يجب أن يتكون من 10 أرقام بالضبط.',
            'phone_number.starts_with' => 'رقم الهاتف يجب أن يبدأ بـ 09.',
            'phone_number.unique'   => 'عذراً، هذا الرقم مسجل مسبقاً في النظام.',
            
            'password.required'     => 'كلمة المرور مطلوبة.',
            'password.min'          => 'كلمة المرور يجب ألا تقل عن 7 خانات.',
            'password.regex'        => 'كلمة المرور يجب أن تحتوي على أرقام وحروف، ويُمنع استخدام الرموز.',
            
            'password_confirmation.same' => 'تأكيد كلمة المرور لا يطابق كلمة المرور المدخلة.',
            
            'otp.required'          => 'كود التحقق مطلوب.',
            'otp.numeric'           => 'كود التحقق يجب أن يحتوي على أرقام فقط.',
            'otp.digits'            => 'كود التحقق يجب أن يتكون من 6 أرقام.'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'بيانات غير صالحة.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => 1,
            'role_id'    => 2, 
            'created_by' => 1, 
        ]);
    }

    public function rules(): array
    {
        return [
            // يدعم العربية والانجليزية ويشترط 3 مقاطع على الأقل لتوثيق الهوية
            'full_name' => [
                'required',
                'string',
                'unique:users,full_name',
                function ($attribute, $value, $fail) {
                    $words = explode(' ', trim(preg_replace('/\s+/', ' ', $value)));
                    if (count($words) < 3) {
                        $fail('الرجاء إدخل الاسم الثلاثي للمشرف بالكامل لتوثيق الحساب.');
                    }
                }
            ],
            'email' => [
                'required',
                'email',
                'unique:users,email'
            ],
            'phone_number' => [
                'required',
                'numeric',
                'digits:10',
                'regex:/^09/',
                'unique:users,phone_number'
            ],
            'password' => [
                'nullable',
                'string',
                'min:6'
            ],
            'is_active'  => 'required|boolean',
            'role_id'    => 'required|integer',
            'created_by' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'حقل الاسم الكامل مطلوب، لا يمكنك تركه فارغاً.',
            'full_name.string'   => 'الاسم يجب أن يكون نصاً صالحاً وخالياً من الرموز.',
            'full_name.unique'   => 'هذا الاسم مسجل في النظام مسبقاً، الرجاء اختيار اسم مختلف.',

            'email.required'     => 'البريد الإلكتروني حقل إجباري لتسجيل حساب المشرف.',
            'email.email'        => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'       => 'البريد الإلكتروني هذا مستخدم لحساب آخر في النظام.',

            'phone_number.required' => 'رقم الهاتف مطلوب لاستكمال عملية التسجيل.',
            'phone_number.numeric'  => 'رقم الهاتف يجب أن يحتوي على أرقام فقط.',
            'phone_number.digits'   => 'رقم الهاتف يجب أن يتكون من 10 أرقام بالضبط.',
            'phone_number.regex'    => 'رقم الهاتف غير صحيح، يجب أن يبدأ بـ 09.',
            'phone_number.unique'   => 'رقم الهاتف هذا مستخدم لحساب آخر بالفعل.',

            'password.min'          => 'كلمة المرور يجب ألا تقل عن 6 خانات.'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'عذراً، مدخلات إنشاء الحساب تحتوي على أخطاء.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
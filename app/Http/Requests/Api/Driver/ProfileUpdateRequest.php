<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!auth()->check()) return false;
        return auth()->user()->is_active !== 0; 
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'full_name' => [
                'sometimes', 'string', 'min:10', 'max:100', 'regex:/^[\p{L} ]+/u',
                function ($attribute, $value, $fail) {
                    $words = explode(' ', trim(preg_replace('/\s+/', ' ', $value)));
                    if (count($words) < 3) {
                        $fail('يجب إدخال الاسم الثلاثي بالكامل.');
                    }
                },
                Rule::unique('users', 'full_name')->ignore($userId),
            ],
            'email' => [
                'sometimes', 'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'phone_number' => [
                'sometimes', 'numeric', 'digits:10', 'regex:/^09/',
                Rule::unique('users', 'phone_number')->ignore($userId)
            ],
            'alternative_phone' => [
                'nullable', 'numeric', 'digits:10', 'regex:/^09/',
                Rule::unique('users', 'alternative_phone')->ignore($userId)
            ],
            'password' => [
                'nullable', 'string', 'min:6', 'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'
            ],
            'gender' => ['sometimes', 'in:male,female'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048']
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.min'            => 'يرجى إدخال الاسم الثلاثي على الأقل.',
            'full_name.unique'         => 'هذا الاسم مسجل في النظام مسبقاً لحساب آخر.',
            'email.email'              => 'تنسيق البريد الإلكتروني غير صحيح.',
            'email.unique'             => 'هذا البريد الإلكتروني مستخدم بالفعل مسبقاً.',
            'phone_number.digits'      => 'يجب أن يتكون رقم الهاتف من 10 أرقام بالضبط.',
            'phone_number.regex'       => 'رقم الهاتف غير صحيح، يجب أن يبدأ بـ 09.',
            'phone_number.unique'      => 'رقم الهاتف هذا مستخدم من قبل حساب آخر.',
            'alternative_phone.digits' => 'يجب أن يتكون رقم الهاتف البديل من 10 أرقام بالضبط.',
            'alternative_phone.regex'  => 'رقم الهاتف البديل غير صحيح، يجب أن يبدأ بـ 09.',
            'alternative_phone.unique' => 'رقم الهاتف البديل هذا مستخدم من قبل حساب آخر.',
            'password.min'             => 'يجب ألا تقل كلمة المرور المحدثة عن 6 خانات.',
            'password.regex'           => 'كلمة المرور يجب أن تحتوي على حرف ورقم واحد على الأقل للأمان.',
            'gender.in'                => 'القيمة المختارة للجنس غير صحيحة.',
            'avatar.image'             => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'avatar.mimes'             => 'يسمح فقط بالصور بصيغ jpeg, png, jpg.',
            'avatar.max'               => 'حجم الصورة الشخصية يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'عذراً، البيانات المرسلة لتعديل الحساب تحتوي على أخطاء.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
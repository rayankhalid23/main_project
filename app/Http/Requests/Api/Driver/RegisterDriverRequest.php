<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class RegisterAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'full_name'    => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                $words = explode(' ', trim(preg_replace('/\s+/', ' ', $value)));
                if (count($words) < 3) $fail('يجب إدخال الاسم الثلاثي بالكامل لضمان توثيق الهوية.');
            }],
            'phone_number' => 'required|digits:10|regex:/^09[0-9]{8}$/|unique:users,phone_number',
            'password'     => 'required|min:6|regex:/[a-zA-Z]/|regex:/[0-9]/',
            'avatar_url'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'    => 'اسم المستخدم مطلوب.',
            'full_name.max'         => 'الاسم طويل جداً.',
            'phone_number.required' => 'رقم الهاتف مطلوب.',
            'phone_number.digits'   => 'رقم الهاتف يجب أن يتكون من 10 أرقام.',
            'phone_number.regex'    => 'صيغة رقم الهاتف غير صحيحة (يجب أن يبدأ بـ 09).',
            'phone_number.unique'   => 'هذا الرقم مستخدم مسبقاً من قبل سائق آخر.',
            'password.required'     => 'كلمة المرور مطلوبة.',
            'password.min'          => 'كلمة المرور يجب ألا تقل عن 6 خانات.',
            'password.regex'        => 'كلمة المرور يجب أن تحتوي على أحرف وأرقام.',
            'avatar_url.image'      => 'يجب أن يكون الملف صورة.',
            'avatar_url.max'        => 'حجم الصورة الشخصية يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['status' => false, 'message' => 'بيانات الحساب غير صالحة', 'errors' => $validator->errors()], 422));
    }
}
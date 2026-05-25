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
     * قواعد التحقق من البيانات
     */
    public function rules(): array
    {
        return [
            'phone_number' => [
                'required',
                'digits:10',
                'regex:/^09[0-9]{8}$/'
            ],
            'code' => 'sometimes|required|digits:6',
        ];
    }

    /**
     * رسائل الخطأ المخصصة
     */
    public function messages(): array
    {
        return [
            'phone_number.required' => 'يرجى إدخال رقم الهاتف.',
            'phone_number.digits'   => 'رقم الهاتف يجب أن يتكون من 10 أرقام بالضبط.',
            'phone_number.regex'    => 'رقم الهاتف غير صحيح، يجب أن يبدأ بـ 09.',
            'code.required'         => 'يرجى إدخال رمز التحقق.',
            'code.digits'           => 'رمز التحقق يجب أن يتكون من 6 أرقام.',
        ];
    }

    /**
     * التعامل مع فشل التحقق لإرجاع استجابة JSON موحدة
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'بيانات غير صالحة.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
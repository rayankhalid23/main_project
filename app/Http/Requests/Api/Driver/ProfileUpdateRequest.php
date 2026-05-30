<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Exception;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * تحديد صلاحية المستخدم (يجب أن يكون مسجلاً للدخول)
     */
    public function authorize(): bool
    {
        // نفترض أن هذا المسار محمي بـ Middleware auth:sanctum
        return auth()->check();
    }

    /**
     * قواعد التحقق للتعديل الجزئي (Partial Update)
     * استخدام 'sometimes' هو السر هنا: يتم التحقق فقط إذا تم إرسال الحقل.
     */
    public function rules(): array
    {
        // جلب معرف المستخدم الحالي لاستثنائه من قواعد الـ unique
        $userId = auth()->id();
        
        // جلب معرف السائق المرتبط بالمستخدم الحالي (لاستثناء رقم الرخصة والبطاقة)
        $driverId = auth()->user()->driver->id ?? null;

        return [
            // --- بيانات الحساب ---
            'full_name' => [
                'sometimes', // <--- هذا يعني: تحقق فقط إذا تم إرسال الحقل
                'required',
                'string',
                'regex:/^[\p{L} ]+/u',
                function ($attribute, $value, $fail) {
                    $words = explode(' ', trim(preg_replace('/\s+/', ' ', $value)));
                    if (count($words) < 3) {
                        $fail('يجب إدخال الاسم الثلاثي بالكامل.');
                    }
                },
            ],
            'phone_number' => [
                'sometimes',
                'required',
                'digits:10',
                'regex:/^09[0-9]{8}$/',
                // استثناء المستخدم الحالي من قاعدة التحقق لتجنب خطأ التكرار
                'unique:users,phone_number,' . $userId
            ],
            'password' => [
                'sometimes',
                'required',
                'min:6',
                'regex:/[a-zA-Z]/',
                'regex:/[0-9]/',
            ],
            'avatar_url' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',

            // --- بيانات السائق (يمكنك إزالة ما لا ترغب للسائق بتعديله بنفسه) ---
            'national_id' => [
                'sometimes',
                'required',
                'digits:12',
                'regex:/^[12][0-9]{11}$/',
                'unique:drivers,national_id,' . $driverId
            ],
            'license_number' => [
                'sometimes',
                'required',
                'regex:/^[0-9]+$/',
                'unique:drivers,license_number,' . $driverId
            ],
            'license_expiry' => 'sometimes|required|date|after:today',
        ];
    }

    /**
     * رسائل الخطأ المخصصة (نفس أسلوبك السابق)
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'حقل الاسم لا يمكن أن يكون فارغاً إذا أردت تعديله.',
            'phone_number.unique'  => 'رقم الهاتف هذا مستخدم في حساب آخر.',
            'avatar_url.image' => 'يجب أن يكون الملف المرفوع صورة.',
            // ... يمكنك إضافة باقي الرسائل بنفس النمط
        ];
    }

    /**
     * معالجة فشل التحقق (حماية النظام وإرجاع 422)
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'فشل التحقق من البيانات، يرجى تصحيح الأخطاء أدناه.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
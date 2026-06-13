<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionStatusRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان السائق مخولاً لإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق من رد السائق (قبول أو رفض)
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['accepted', 'rejected'])],
            
            // حقل الملاحظات أو سبب الرفض يكون مطلوباً فقط في حال رفض السائق للطلب تجارياً
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * رسائل الخطأ المخصصة
     */
    public function messages(): array
    {
        return [
            'status.in'                  => 'الحالة المرسلة غير صحيحة، يجب أن تكون accepted أو rejected.',
            'rejection_reason.required_if' => 'يجب تدوين سبب الرفض لتوضيحه لولي الأمر.',
        ];
    }
}
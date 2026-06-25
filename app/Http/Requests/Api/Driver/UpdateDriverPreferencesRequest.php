<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\driver\DriverShift;

class UpdateDriverPreferencesRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مخولاً لإجراء هذا الطلب
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق المطبقة على الطلب
     */
    public function rules(): array
    {
        return [
            'shift'   => ['required', Rule::enum(DriverShift::class)],
            'zones'   => ['required', 'array', 'min:1'],
            'zones.*' => ['required', 'integer', 'exists:zones,id'],
        ];
    }

    /**
     * رسائل الخطأ المخصصة باللغة العربية لـ Front-end
     */
    public function messages(): array
    {
        return [
            'shift.required' => 'يرجى اختيار الفترة الزمنية للعمل.',
            'shift.enum'     => 'الفترة الزمنية المحددة غير صالحة في النظام.',
            'zones.required' => 'يجب عليك اختيار منطقة عمل واحدة على الأقل.',
            'zones.array'    => 'تنسيق المناطق الجغرافية غير صحيح.',
            'zones.*.exists' => 'تنبيه: إحدى المناطق التي قمت باختيارها غير مسجلة بالنظام.',
        ];
    }
}
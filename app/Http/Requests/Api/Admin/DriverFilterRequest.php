<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DriverFilterRequest extends FormRequest
{
    /**
     * التحقق من الصلاحية الأمنية للأدمن
     */
    public function authorize(): bool
    {
        // نتحقق من أن المستخدم مسجل دخول وله رتبة أدمن (يمكنك تعديل الشرط حسب نظام الـ Roles لديك)
        return auth()->check() && auth()->user()->role_id !== null; 
    }

    /**
     * قواعد التحقق لفلترة السائقين
     */
    public function rules(): array
    {
        return [
            // الفلترة حسب الحالات الستة الموجودة في Enum قاعدة البيانات لديك بدقة
            'status' => ['nullable', 'string', 'in:Pending,Approved,Suspended,Rejected,Offline,ON_TRIP'],
            'search' => ['nullable', 'string', 'max:100'], // للبحث باسم السائق أو بريده أو هاتفه
        ];
    }

    /**
     * رسائل الخطأ المخصصة باللغة العربية
     */
    public function messages(): array
    {
        return [
            'status.in' => 'الحالة المحددة للفلترة غير صحيحة ولا تطابق النظام.',
            'search.max' => 'نص البحث طويل جداً، يرجى الاختصار.',
        ];
    }

    /**
     * رد موحد في حال فشل التحقق للـ APIs المتوافقة مع لوحة التحكم
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'عذراً، مدخلات الفلترة أو البحث تحتوي على أخطاء.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
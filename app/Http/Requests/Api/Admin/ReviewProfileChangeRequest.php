<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReviewProfileChangeRequest extends FormRequest
{
    /**
     * التحقق من الصلاحية الأمنية للأدمن
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role_id !== null;
    }

    /**
     * قواعد مراجعة طلب التعديل المعلق (قبول أو رفض مسبب)
     */
    public function rules(): array
    {
        return [
            // القرار هنا يُمرر كـ decision ليتطابق مع بارامترات دالة السيرفس المحدثة
            'decision'         => ['required', 'string', 'in:Approved,Rejected'],
            
            // إلزامية مبرر الرفض في حال رفض التعديلات، ومنعه تماماً في حال القبول
            'rejection_reason' => [
                'required_if:decision,Rejected',
                'prohibited_if:decision,Approved',
                'nullable',
                'string',
                'min:10',
                'max:500'
            ],
        ];
    }

    /**
     * رسائل الخطأ المخصصة والموجهة للأدمن باللغة العربية
     */
    public function messages(): array
    {
        return [
            'decision.required'             => 'يجب تحديد قرار الأدمن (Approved أو Rejected) على هذا التعديل.',
            'decision.in'                   => 'القرار المتخذ يجب أن يكون إما Approved أو Rejected فقط.',
            'rejection_reason.required_if'   => 'عند رفض تعديلات السائق، يرجى كتابة سبب الرفض توضيحياً (لا يقل عن 10 أحرف).',
            'rejection_reason.prohibited_if' => 'لا يمكن كتابة سبب رفض لطلب تعديل تمت الموافقة عليه وتطبيقه.',
            'rejection_reason.min'          => 'سبب الرفض قصير جداً، يرجى تقديم مبرر أوضح للسائق.',
            'rejection_reason.max'          => 'سبب الرفض طويل جداً، يرجى الاختصار.',
        ];
    }

    /**
     * رد موحد في حال فشل التحقق متوافق مع لوحة التحكم
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'تعذر معالجة قرار التعديل بسبب أخطاء في المدخلات المرسلة.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
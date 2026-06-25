<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DriverReviewRequest extends FormRequest
{
    /**
     * التحقق من الصلاحية الأمنية للأدمن
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role_id !== null;
    }

    /**
     * قواعد مراجعة طلب السائق (قبول ذكي أو رفض مسبب)
     */
    public function rules(): array
    {
        return [
            // القرار يجب أن يكون إما قبول أو رفض فقط في هذه الشاشة
            'status' => ['required', 'string', 'in:Approved,Rejected'],
            
            // شرط احترافي: سبب الرفض مطلوب إلزامياً فقط إذا كانت الحالة Rejected وممنوع تماماً إذا كانت Approved
            'rejection_reason' => [
                'required_if:status,Rejected',
                'prohibited_if:status,Approved',
                'nullable',
                'string',
                'min:10',
                'max:500'
            ],
        ];
    }

    /**
     * رسائل الخطأ المخصصة والموجهة للأدمن
     */
    public function messages(): array
    {
        return [
            'status.required'           => 'يجب تحديد قرار الأدمن بالقبول أو الرفض أولاً.',
            'status.in'                 => 'القرار المتخذ يجب أن يكون إما Approved أو Rejected.',
            'rejection_reason.required_if' => 'يرجى كتابة سبب الرفض توضيحياً للسائق (لا يقل عن 10 أحرف).',
            'rejection_reason.prohibited_if' => 'لا يمكن إضافة سبب رفض لطلب تم قبوله واعتماده.',
            'rejection_reason.min'      => 'سبب الرفض قصير جداً، يرجى كتابة تفاصيل واضحة للسائق.',
            'rejection_reason.max'      => 'سبب الرفض طويل جداً، يرجى الاختصار لتسهيل القراءة.',
        ];
    }

    /**
     * رد موحد في حال فشل مراجعة الطلب
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'تعذر إتمام مراجعة الطلب بسبب أخطاء في المدخلات.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
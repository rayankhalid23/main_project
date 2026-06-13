<?php

namespace App\Http\Requests\Api\Shared;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContractRequest extends FormRequest
{
    /**
     * السماح لجميع المستخدمين الموثقين بالوصول
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق الصارمة الخاصة بإنشاء العقد
     */
    public function rules(): array
    {
        return [
            'subscription_request_id' => 'required|integer|exists:requests,id',
            'price'                   => 'required|numeric|min:0',
            'pickup_time'             => 'required|date_format:H:i',
            'dropoff_time'            => 'required|date_format:H:i',
            'max_waiting_time'        => 'required|integer|min:1|max:15',
            'selected_clauses'        => 'required|array|min:1',
            'selected_clauses.*'      => 'integer|exists:clauses,id',
        ];
    }

    /**
     * 🎯 تخصيص رسائل الخطأ الدقيقة لجميع الحالات الممكنة
     */
    public function messages(): array
    {
        return [
            // طلب الاشتراك
            'subscription_request_id.required' => 'رقم طلب الاشتراك حقل إجباري ولا يمكن تركه فارغاً.',
            'subscription_request_id.integer'  => 'يجب أن يكون رقم طلب الاشتراك عبارة عن قيمة رقمية صحيحة.',
            'subscription_request_id.exists'   => 'طلب الاشتراك المحدد غير موجود في النظام، أو قد يكون تم إلغاؤه.',

            // القيمة المالية
            'price.required'                   => 'قيمة الاشتراك المالي حقل إجباري لتحديد الاتفاق المالي بين الطرفين.',
            'price.numeric'                    => 'يجب إدخال تكلفة الاشتراك كقيمة رقمية (أرقام فقط بدون حروف).',
            'price.min'                        => 'لا يمكن أن تكون قيمة الاشتراك المالي أقل من 0 (صفر).',

            // وقت الحضور (صباحاً)
            'pickup_time.required'             => 'تحديد وقت الحضور صباحاً حقل إجباري لجدولة الرحلة.',
            'pickup_time.date_format'          => 'يجب إدخال وقت الحضور بصيغة وقت صحيحة مطابقة للنظام (ساعة:دقيقة مثل 07:15).',

            // وقت الإرجاع (مساءً)
            'dropoff_time.required'            => 'تحديد وقت الإرجاع مساءً حقل إجباري لجدولة الرحلة.',
            'dropoff_time.date_format'         => 'يجب إدخال وقت الإرجاع بصيغة وقت صحيحة مطابقة للنظام (ساعة:دقيقة مثل 14:30).',

            // حد الانتظار الأقصى
            'max_waiting_time.required'        => 'يجب تحديد الحد الأقصى لانتظار الطالب أمام المنزل.',
            'max_waiting_time.integer'         => 'يجب أن يكون حد الانتظار عبارة عن عدد صحيح يمثل الدقائق.',
            'max_waiting_time.min'             => 'الحد الأدنى لانتظار السائق عند الباب هو دقيقة واحدة على الأقل.',
            'max_waiting_time.max'             => 'الحد الأقصى المسموح به لانتظار السائق هو 15 دقيقة كحد أقصى لتجنب تأخير بقية الطلاب.',

            // الشروط والقوانين المختارة
            'selected_clauses.required'        => 'يجب اختيار بند قانوني واحد على الأقل لتوليد العقد وملء بنوده.',
            'selected_clauses.array'           => 'يجب إرسال الشروط القانونية المختارة على هيئة مصفوفة (Array).',
            'selected_clauses.min'             => 'يلزم اختيار شرط واحد على الأقل من مكتبة القوانين لإتمام عملية الإنشاء.',
            'selected_clauses.*.integer'       => 'معرّف البند المرسل داخل مصفوفة الشروط يجب أن يكون رقماً صحيحاً متسلسلاً.',
            'selected_clauses.*.exists'        => 'واحد أو أكثر من البنود أو الشروط التي تم اختيارها غير معرّف في مكتبة القوانين لدينا.',
        ];
    }

    /**
     * تخصيص الاستجابة الفاشلة لتظهر في الـ API كـ JSON نظيف ومفهوم للفرونت إند
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'عذراً، بعض المدخلات التي أرسلتها غير صالحة، يرجى مراجعة الأخطاء.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
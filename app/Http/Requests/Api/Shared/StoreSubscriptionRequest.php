<?php

namespace App\Http\Requests\Api\Shared;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مخولاً لإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        // تفعيل الصلاحية (يمكنك ربطها بـ Guard ولي الأمر لاحقاً)
        return true; 
    }

    /**
     * قواعد التحقق الصارمة لضمان سلامة البيانات ومنع التلاعب.
     */
    public function rules(): array
{
    return [
        'driver_id'                      => 'required|integer|exists:drivers,id',
        'school_id'                      => 'required|integer|exists:schools,id',
        'timing'                         => 'required|string|in:MORNING,EVENING,BOTH',
        'notes'                          => 'nullable|string|max:500',
        
        // التحقق الصارم من مصفوفة الأطفال وحقولها الداخلية
        'children'                       => 'required|array|min:1',
        'children.*.child_id'            => 'required|integer|exists:children,id',
        'children.*.pickup_location_id'  => 'required|integer|exists:addresses,id',
        'children.*.dropoff_location_id' => 'required|integer|exists:addresses,id', // 💡 هذا السطر يضمن عبور القيمة وعدم حذفها
        'children.*.notes'               => 'nullable|string|max:255',
    ];
}
    /**
     * رسائل الخطأ المخصصة لتظهر بشكل احترافي في الـ API فرونت إند.
     */
    public function messages(): array
    {
        return [
            'driver_id.exists'              => 'السائق المحدد غير موجود في النظام.',
            'school_id.exists'              => 'المدرسة المحددة غير مسجلة لدينا.',
            'timing.in'                     => 'التوقيت المختار غير صحيح، يجب أن يكون MORNING أو EVENING أو BOTH.',
            'children.required'             => 'يجب تحديد طفل واحد على الأقل لإتمام طلب الاشتراك.',
            'children.*.child_id.exists'    => 'أحد الأطفال المحددين غير موجود في النظام.',
            'children.*.pickup_location_id.exists' => 'عنوان الركوب المحدد للطفل غير صحيح.',
        ];
    }
}
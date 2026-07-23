<?php

namespace App\Http\Requests\Api\Shared;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Shared\SubscriptionRequest;

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
        'driver_id'         => 'required|integer|exists:drivers,id',
        'school_id'         => 'required|integer|exists:schools,id',
        'subscription_type' => 'nullable|string|in:monthly,daily',
        'direction'         => 'nullable|string|in:go,return,both',
        'timing'            => 'nullable|string|in:MORNING,EVENING,BOTH',
        'start_date'        => 'nullable|date',
        'end_date'          => 'nullable|date',
        
        'children'                      => 'required|array|min:1',
        'children.*.child_id'           => 'required|integer|exists:children,id',
        'children.*.pickup_address_id'  => 'required|integer|exists:addresses,id',
        'children.*.dropoff_address_id' => 'required|integer|exists:schools,id',
        'children.*.price_per_child'    => 'required|numeric|min:0',
        'children.*.child_notes'        => 'nullable|string',
        'children.*.subscription_type'  => 'required|string|in:monthly,daily',
        'children.*.direction'          => 'required|string|in:go,return,both',
        'children.*.timing'             => 'required|string|in:MORNING,EVENING,BOTH',
        'children.*.start_date'         => 'required|date',
        'children.*.end_date'           => 'nullable|date',
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
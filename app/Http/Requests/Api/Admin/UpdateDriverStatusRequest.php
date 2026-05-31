<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // مسموح لأن الحماية تتم عبر Middleware الأدمن
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:Approved,Rejected',
            // حقل سبب الرفض مطلوب إجباري فقط إذا كانت الحالة الممررة هي Rejected
            'rejection_reason' => 'required_if:status,Rejected|string|nullable|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'حالة السائق مطلوبة.',
            'status.in' => 'الحالة يجب أن تكون إما Approved أو Rejected.',
            'rejection_reason.required_if' => 'يجب كتابة سبب الرفض عند اختيار حالة الرفض.',
        ];
    }
}
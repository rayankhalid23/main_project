<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Admin\Admin;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $adminParam = $this->route('admin'); 
        $admin = $adminParam instanceof Admin ? $adminParam : Admin::find($adminParam);
        $userId = $admin ? $admin->user_id : null;

        return [
            'full_name' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('users', 'full_name')->ignore($userId),
                'regex:/^([\p{L}]+\s+){2,}[\p{L}]+$/u'
            ],
            'phone_number' => [
                'sometimes',
                'required',
                'numeric',
                'digits:10',
                'regex:/^09/',
                Rule::unique('users', 'phone_number')->ignore($userId)
            ],
            'password' => [
                'nullable',
                'string',
                'min:6',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'
            ],
            'is_active' => ['sometimes', 'boolean'],
            'avatar_url' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            
            // تم دمج القواعد الإضافية هنا:
            'status' => ['required', 'in:Approved,Rejected'],
            'rejection_reason' => ['required_if:status,Rejected', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'حقل الاسم مطلوب.',
            'full_name.unique'   => 'هذا الاسم مسجل في النظام مسبقاً.',
            'full_name.regex'    => 'الرجاء إدخال الاسم الثلاثي بشكل صحيح.',
            'phone_number.digits'=> 'رقم الهاتف يجب أن يتكون من 10 أرقام.',
            'phone_number.regex' => 'رقم الهاتف يجب أن يبدأ بـ 09.',
            'phone_number.unique'=> 'رقم الهاتف هذا مستخدم لحساب آخر.',
            'password.min'       => 'يجب ألا تقل كلمة المرور عن 6 خانات.',
            'password.regex'     => 'كلمة المرور يجب أن تحتوي على حرف ورقم.',
            'avatar_url.mimes'   => 'يجب أن تكون الصورة بصيغة jpeg, png, أو jpg.',
            'avatar_url.max'     => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
            'status.in'          => 'حالة المشرف يجب أن تكون إما Approved أو Rejected.',
            'rejection_reason.required_if' => 'يجب كتابة سبب الرفض عند اختيار Rejected.',
        ];
    }
}
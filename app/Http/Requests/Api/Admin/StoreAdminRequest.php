<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
    /**
     * تحديد صلاحية استخدام هذا الطلب
     */
    public function authorize(): bool
    {
        return true; // سيتم حمايته لاحقاً عبر الـ Middleware
    }

    /**
     * [لمسة احترافية]: دمج القيم الافتراضية الثابتة قبل بدء الفحص
     * هذا يمنع أي محاولة اختراق لتغيير نوع الحساب أو حالته من الفرونت إند
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => 1,
            'role_id'    => 2, // 2 = Admin
            'created_by' => 1, // معرف السوبر أدمن الافتراضي حالياً
        ]);
    }

    /**
     * قواعد التحقق الصارمة
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // الاسم: مطلوب، نص، غير مكرر، ويجب أن يكون 3 مقاطع (باستخدام التعبير النمطي للغة العربية)
            'full_name' => [
                'required',
                'string',
                'unique:users,full_name',
                'regex:/^([\p{L}]+\s+){2,}[\p{L}]+$/u'
            ],

            // الهاتف: مطلوب، غير مكرر، أرقام فقط، 10 خانات، ويبدأ بـ 09
            'phone_number' => [
                'required',
                'numeric',
                'digits:10',
                'regex:/^09/',
                'unique:users,phone_number'
            ],

            // كلمة المرور: مطلوبة، 6 خانات على الأقل، وتحتوي على أحرف وأرقام
            'password' => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'
            ],

            // الحقول المدمجة (نتأكد من نوعها فقط)
            'is_active'  => 'required|boolean',
            'role_id'    => 'required|integer',
            'created_by' => 'required|integer',
        ];
    }

    /**
     * رسائل الخطأ الذكية والاحترافية المخصصة لكل حالة
     */
    public function messages(): array
    {
        return [
            // رسائل خطأ الاسم
            'full_name.required' => 'حقل الاسم مطلوب، لا يمكنك تركه فارغاً.',
            'full_name.string'   => 'الاسم يجب أن يكون نصاً صالحاً.',
            'full_name.unique'   => 'هذا الاسم مسجل في النظام مسبقاً، الرجاء اختيار اسم آخر.',
            'full_name.regex'    => 'الرجاء إدخال الاسم الثلاثي بشكل صحيح (مثل: أحمد محمد علي).',

            // رسائل خطأ رقم الهاتف
            'phone_number.required' => 'رقم الهاتف مطلوب لاستكمال التسجيل.',
            'phone_number.numeric'  => 'رقم الهاتف يجب أن يحتوي على أرقام فقط دون مسافات أو رموز.',
            'phone_number.digits'   => 'رقم الهاتف يجب أن يتكون من 10 أرقام بالضبط.',
            'phone_number.regex'    => 'رقم الهاتف يجب أن يبدأ بـ 09.',
            'phone_number.unique'   => 'رقم الهاتف هذا مستخدم لحساب آخر، يرجى التأكد.',

            // رسائل خطأ كلمة المرور
            'password.required' => 'كلمة المرور مطلوبة لحماية الحساب.',
            'password.min'      => 'يجب ألا تقل كلمة المرور عن 6 خانات.',
            'password.regex'    => 'كلمة المرور يجب أن تحتوي على حرف إنجليزي واحد على الأقل بالإضافة إلى الأرقام لضمان قوتها.',
        ];
    }
}
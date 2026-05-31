<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Admin\Admin;

class UpdateAdminRequest extends FormRequest
{
    /**
     * تحديد صلاحية استخدام هذا الطلب
     */
    public function authorize(): bool
    {
        return true; // سيتم حمايته عبر Middleware الصلاحيات
    }

    /**
     * قواعد التحقق الخاصة بالتحديث الجزئي (Partial Update)
     */
    public function rules(): array
    {
        // استخراج معرف المستخدم (user_id) المرتبط بهذا المشرف من الرابط
        // لكي نستثنيه من فحص التكرار في قاعدة البيانات
        $adminParam = $this->route('admin'); 
        $admin = $adminParam instanceof Admin ? $adminParam : Admin::find($adminParam);
        $userId = $admin ? $admin->user_id : null;

        return [
            // استخدام 'sometimes' يعني: إذا لم يرسل الفرونت إند الاسم، لا تطلب منه شيئاً.
            // ولكن إذا أرسله، يجب أن يطبق هذه الشروط الصارمة.
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

            // في التحديث: كلمة المرور اختيارية (nullable). إذا أرسلها فارغة يتجاهلها السيرفر، 
            // وإذا كتب فيها شيئاً، يجبره على القواعد الصارمة.
            'password' => [
                'nullable',
                'string',
                'min:6',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'
            ],

            // حالة الحساب يمكن تحديثها لإيقاف أو تفعيل المشرف
            'is_active' => [
                'sometimes',
                'boolean'
            ],

            // حماية السيرفر من الملفات الخبيثة والأحجام الكبيرة
            'avatar_url' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048'
            ]
        ];
    }

    /**
     * رسائل الخطأ الذكية والاحترافية المخصصة
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'حقل الاسم مطلوب، لا يمكنك إرساله فارغاً.',
            'full_name.string'   => 'الاسم يجب أن يكون نصاً صالحاً.',
            'full_name.unique'   => 'هذا الاسم مسجل في النظام مسبقاً، الرجاء اختيار اسم آخر.',
            'full_name.regex'    => 'الرجاء إدخال الاسم الثلاثي بشكل صحيح (مثل: أحمد محمد علي).',

            'phone_number.required' => 'لا يمكنك تفريغ رقم الهاتف.',
            'phone_number.numeric'  => 'رقم الهاتف يجب أن يحتوي على أرقام فقط.',
            'phone_number.digits'   => 'رقم الهاتف يجب أن يتكون من 10 أرقام بالضبط.',
            'phone_number.regex'    => 'رقم الهاتف يجب أن يبدأ بـ 09.',
            'phone_number.unique'   => 'رقم الهاتف هذا مستخدم لحساب آخر في النظام.',

            'password.min'   => 'يجب ألا تقل كلمة المرور عن 6 خانات.',
            'password.regex' => 'كلمة المرور الجديدة يجب أن تحتوي على حرف إنجليزي واحد على الأقل مع الأرقام.',
            
            'avatar_url.image' => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'avatar_url.mimes' => 'يجب أن تكون الصورة بصيغة jpeg, png, أو jpg.',
            'avatar_url.max'   => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }
}
<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Exception;

class RegisterDriverRequest extends FormRequest
{
    /**
     * تحديد صلاحية المستخدم للقيام بهذا الطلب
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق الصارمة والدقيقة لكل حقل
     */
    public function rules(): array
    {
        return [
            // --- 1. بيانات الحساب الشخصي (Users) ---
            'full_name' => [
                'required',
                'string',
                'regex:/^[\p{L}]+/u', // التأكد من أنه يحتوي على نصوص
                function ($attribute, $value, $fail) {
                    // التحقق من أن الاسم ثلاثي على الأقل (يحتوي على مسافتين بين الكلمات)
                    $words = explode(' ', trim(preg_replace('/\s+/', ' ', $value)));
                    if (count($words) < 3) {
                        $fail('يجب إدخال الاسم الثلاثي بالكامل.');
                    }
                },
            ],
            'phone_number' => [
                'required',
                'digits:10',
                'regex:/^09[0-9]{8}$/',
                'unique:users,phone_number'
            ],
            'password' => [
                'required',
                'min:6',
                'regex:/[a-zA-Z]/', // حرف واحد على الأقل
                'regex:/[0-9]/',     // أرقام
            ],
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // اختيارية

            // --- 2. بيانات السائق المهنية (Drivers) ---
            'national_id' => [
                'required',
                'digits:12',
                'regex:/^[12][0-9]{11}$/', // يبدأ بـ 1 أو 2 ويتلوه 11 رقماً
                'unique:drivers,national_id'
            ],
            'license_number' => [
                'required',
                'regex:/^[0-9]+$/', // أرقام فقط
                'unique:drivers,license_number'
            ],
            'license_expiry' => 'required|date|after:today',

            // --- 3. بيانات المركبة (Vehicles) ---
            'plate_number' => [
                'required',
                'max:6',
                'regex:/^[0-9-]+$/', // أرقام وعلامة الشرطة (-) فقط
                'unique:vehicles,plate_number'
            ],
            'brand'             => 'required|string|max:50',
            'model'             => 'required|string|max:50',
            'year'              => 'required|digits:4|integer|min:1900|max:' . (date('Y') + 1),
            'color'             => 'required|string|max:30',
            'type'              => 'required|in:Bus,Sedan,Van',
            'capacity_manual'   => 'required|integer|min:1',
            'vehicle_image_url' => 'required|image|mimes:jpeg,png,jpg|max:4096', // ضرورية
            'has_ac'            => 'required|boolean',

            // --- 4. الوثائق والمستندات (Driver Documents) ---
            'doc_license'         => 'required|image|mimes:jpeg,png,jpg|max:4096', // صورة الرخصة
            'doc_logbook'         => 'required|image|mimes:jpeg,png,jpg|max:4096', // صورة الكتيب
            'doc_insurance'       => 'required|image|mimes:jpeg,png,jpg|max:4096', // صورة التأمين
            'doc_criminal_record' => 'required|image|mimes:jpeg,png,jpg|max:4096', // السجل الجنائي
        ];
    }

    /**
     * رسائل خطأ دقيقة ومخصصة لكل حالة على حدة
     */
    public function messages(): array
    {
        return [
            // الاسم
            'full_name.required' => 'حقل الاسم الثلاثي مطلوب.',
            'full_name.string'   => 'يجب أن يكون الاسم نصاً صحيحاً.',

            // رقم الهاتف
            'phone_number.required' => 'رقم الهاتف مطلوب لتسجيل الحساب.',
            'phone_number.digits'   => 'رقم الهاتف يجب أن يتكون من 10 خانات فقط.',
            'phone_number.regex'    => 'رقم الهاتف غير صحيح، يجب أن يبدأ بـ 09 ويتكون من أرقام فقط.',
            'phone_number.unique'   => 'رقم الهاتف هذا مسجل بالفعل في النظام.',

            // الرقم السري
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min'      => 'كلمة المرور يجب ألا تقل عن 6 خانات (أرقام وحروف).',
            'password.regex'    => 'كلمة المرور ضعيفة، يجب أن تحتوي على 6 أرقام وحرف واحد على الأقل.',

            // الصورة الشخصية
            'avatar_url.image' => 'الملف المرفق للصورة الشخصية يجب أن يكون صورة فقط.',
            'avatar_url.mimes' => 'امتداد الصورة الشخصية يجب أن يكون (jpeg, png, jpg).',
            'avatar_url.max'   => 'حجم الصورة الشخصية يجب ألا يتجاوز 2 ميجابايت.',

            // الرقم الوطني
            'national_id.required' => 'الرقم الوطني مطلوب وضروري.',
            'national_id.digits'   => 'الرقم الوطني يجب أن يتكون من 12 خانة بالضبط.',
            'national_id.regex'    => 'الرقم الوطني غير صحيح، يجب أن يتكون من 12 رقماً ويبدأ بالرقم 1 أو 2.',
            'national_id.unique'   => 'الرقم الوطني هذا مسجل مسبقاً لسائق آخر.',

            // رخصة القيادة
            'license_number.required' => 'رقم رخصة القيادة مطلوب.',
            'license_number.regex'    => 'رقم رخصة القيادة غير صحيح، لا يقبل إلا أرقاماً فقط.',
            'license_number.unique'   => 'رقم رخصة القيادة هذا مسجل مسبقاً في النظام.',
            'license_expiry.required' => 'تاريخ انتهاء رخصة القيادة مطلوب.',
            'license_expiry.date'     => 'صيغة تاريخ انتهاء الرخصة غير صحيحة.',
            'license_expiry.after'    => 'عذراً، لا يمكن تسجيل سائق برخصة قيادة منتهية الصلاحية.',

            // رقم اللوحة والركبة
            'plate_number.required' => 'رقم لوحة المركبة مطلوب.',
            'plate_number.max'      => 'رقم اللوحة لا يمكن أن يتجاوز 6 خانات كحد أقصى.',
            'plate_number.regex'    => 'رقم اللوحة غير صحيح، يقبل فقط أرقام وعلامة الشرطة (-).',
            'plate_number.unique'   => 'رقم اللوحة هذا مسجل لمركبة أخرى في النظام.',
            
            'brand.required'             => 'ماركة المركبة مطلوبة.',
            'model.required'             => 'موديل المركبة مطلوب.',
            'year.required'              => 'سنة صنع المركبة مطلوبة.',
            'year.digits'                => 'سنة الصنع يجب أن تكون من 4 أرقام.',
            'color.required'             => 'لون المركبة مطلوب.',
            'type.required'              => 'نوع المركبة مطلوب (Bus, Sedan, Van).',
            'type.in'                    => 'نوع المركبة المختار غير مدرج بالقائمة.',
            'capacity_manual.required'   => 'سعة الركاب للمركبة مطلوبة.',
            
            'vehicle_image_url.required' => 'صورة المركبة ضرورية ومطلوبة لإتمام التسجيل.',
            'vehicle_image_url.image'    => 'الملف المرفق للمركبة يجب أن يكون صورة.',
            'vehicle_image_url.max'      => 'حجم صورة المركبة كبير جداً، الحد الأقصى 4 ميجابايت.',

            // الوثائق
            'doc_license.required'         => 'يرجى رفع صورة رخصة القيادة لتتم مراجعتها.',
            'doc_logbook.required'         => 'يرجى رفع صورة كتيب المركبة لتتم مراجعته.',
            'doc_insurance.required'       => 'يرجى رفع صورة تأمين المركبة لتتم مراجعته.',
            'doc_criminal_record.required' => 'يرجى رفع صورة السجل الجنائي (الحالة الجنائية) لضمان الأمان.',
            
            'doc_license.image'         => 'ملف الرخصة المرفوع يجب أن يكون صورة.',
            'doc_logbook.image'         => 'ملف الكتيب المرفوع يجب أن يكون صورة.',
            'doc_insurance.image'       => 'ملف التأمين المرفوع يجب أن يكون صورة.',
            'doc_criminal_record.image' => 'ملف السجل الجنائي المرفوع يجب أن يكون صورة.',
        ];
    }

    /**
     * معالجة فشل التحقق وإرجاع أخطاء دقيقة، وحماية النظام من الأخطاء غير المتوقعة
     */
    protected function failedValidation(Validator $validator)
    {
        try {
            // الاستجابة القياسية الاحترافية للأخطاء المتوقعة في المدخلات (422)
            throw new HttpResponseException(response()->json([
                'status'  => false,
                'message' => 'فشل التحقق من البيانات، يرجى تصحيح الأخطاء أدناه.',
                'errors'  => $validator->errors()
            ], 422));

        } catch (HttpResponseException $e) {
            throw $e;
        } catch (Exception $e) {
            // في حال حدوث خطأ غير متوقع تماماً أثناء معالجة البيانات (تأمين كامل)
            Log::critical("Unexpected Error in RegisterDriverRequest: " . $e->getMessage());
            
            throw new HttpResponseException(response()->json([
                'status'  => false,
                'message' => 'حدث خطأ غير متوقع في الخادم أثناء معالجة الطلب.',
                'error_details' => config('app.debug') ? $e->getMessage() : 'يرجى مراجعة إدارة النظام.'
            ], 500));
        }
    }
}
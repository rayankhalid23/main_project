<?php

namespace App\Http\Requests\Api\Parent;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StoreChildRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مخولاً لإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        // تفعيل التحقق (يمكن ربطها بصلاحيات ولي الأمر لاحقاً)
        return true; 
    }

    /**
     * شروط التحقق الصارمة والمتوافقة مع متطلباتك وقاعدة البيانات
     */
    public function rules(): 
    {
        // حساب التواريخ ديناميكياً بناءً على الشروط (من 6 إلى 21 سنة)
        $minDate = Carbon::now()->subYears(21)->format('Y-m-d'); // أقصى حد للعمر 21 سنة (تاريخ ميلاد قديم)
        $maxDate = Carbon::now()->subYears(6)->format('Y-m-d');  // أقل حد للعمر 6 سنوات (تاريخ ميلاد حديث)

        return [
            'parent_id'       => 'required|exists:parents,id',
            'school_id'       => 'required|exists:schools,id',
            'home_address_id' => 'required|exists:addresses,id',
            
            // شرط الاسم الثلاثي: يجب أن يحتوي على 3 كلمات على الأقل يفصل بينها فراغ، ويدعم الحروف العربية
            'full_name'       => [
                'required',
                'string',
                'min:8',
                'max:150',
                'regex:/^[\p{L}]+([\s]+[\p{L}]+){2,}$/u'
            ],
            
            // شرط العمر: يجب أن يكون التاريخ بين الـ 6 والـ 21 سنة بناءً على حسابات Carbon المسبقة
            'birth_date'      => "required|date|after_or_equal:{$minDate}|before_or_equal:{$maxDate}",
            
            'grade'           => 'required|string|max:50',
            
            // الصورة اختيارية، ولكن إذا رُفعت يجب أن تكون ملف صورة حقيقي وبحجم معقول
            'photo'           => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
            
            // الملاحظات الطبية اختيارية
            'medical_notes'   => 'nullable|string|max:1000',
        ];
    }

    /**
     * تخصيص رسائل الخطأ لتظهر باللغة العربية بشكل احترافي للمستخدم النهائي
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'الاسم الثلاثي للطفل مطلوب ولا يمكن تركه فارغاً.',
            'full_name.regex'    => 'يجب إدخال الاسم ثلاثياً بشكل صحيح (مثال: أحمد محمد علي).',
            'birth_date.required'=> 'تاريخ ميلاد الطفل مطلوب.',
            'birth_date.date'    => 'صيغة تاريخ الميلاد غير صحيحة.',
            'birth_date.after_or_equal'  => 'عمر الطفل لا يمكن أن يتجاوز 21 سنة.',
            'birth_date.before_or_equal' => 'يجب أن يكون عمر الطفل 6 سنوات على الأقل للتسجيل.',
            'photo.image'        => 'الملف المرفوع يجب أن يكون صورة.',
            'photo.max'          => 'حجم الصورة يجب أن لا يتجاوز 2 ميجابايت.',
            'parent_id.exists'   => 'ولي الأمر المحدد غير موجود في النظام.',
            'school_id.exists'   => 'المدرسة المحددة غير موجودة في طرابلس.',
            'home_address_id.exists' => 'العنوان المحدد غير موجود.',
        ];
    }
}
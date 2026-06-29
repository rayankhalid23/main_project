<?php

namespace App\Http\Requests\Api\Parent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\driver\DriverShift;

class FilterDriversRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'keyword'           => ['nullable', 'string', 'max:100'],
            'gender'            => ['nullable', 'string', Rule::in(['male', 'female'])],
            'shift'             => ['nullable', Rule::enum(DriverShift::class)],
            
            // 🗺️ الفلترة الجغرافية ثلاثية المستويات الجديدة لطرابلس الكبرى
            'municipality_id'   => ['nullable', 'integer', 'exists:municipalities,id'],
            'sub_municipality_id'=> ['nullable', 'integer', 'exists:sub_municipalities,id'],
            'zones'             => ['nullable', 'array'],
            'zones.*'           => ['integer', 'exists:zones,id'],
            
            // 🔄 الفلترة بحسب نوع الاشتراك المطلوب للطفل
            'subscription_type' => ['nullable', 'string', Rule::in(['daily', 'monthly'])],
            'children_count'    => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'gender.in'                    => 'جنس السائق المحدد يجب أن يكون male أو female فقط.',
            'shift.enum'                   => 'الفترة الزمنية المحددة غير مدعومة بالنظام.',
            'municipality_id.exists'       => 'البلدية الكبرى المحددة غير صحيحة أو غير مسجلة.',
            'sub_municipality_id.exists'   => 'البلدية الفرعية المحددة غير موجودة.',
            'zones.array'                  => 'تنسيق قائمة المناطق الجغرافية يجب أن يكون مصفوفة.',
            'zones.*.exists'               => 'إحدى المناطق المحددة غير مسجلة لدينا بالنظام.',
            'subscription_type.in'         => 'نوع الاشتراك المطلوب للفلترة يجب أن يكون daily أو monthly.',
            'children_count.integer'       => 'عدد الأطفال يجب أن يكون عبارة عن رقم صحيح.',
            'children_count.min'           => 'يجب تحديد طفل واحد كحد أدنى للتحقق من السعة الركابية.',
        ];
    }
}
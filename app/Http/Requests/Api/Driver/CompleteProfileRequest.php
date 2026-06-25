<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // بيانات السائق - تم توحيد الرقم الوطني ليكون 12 رقماً بالضبط بالتوافق مع ملف التحديث
            'national_id'    => 'required|numeric|digits:12',
            'license_number' => 'required|string|max:50',
            'license_expiry' => 'required|date|after:today',
            
            // بيانات المركبة
            'plate_number'      => 'required|string|max:20',
            'brand'             => 'required|string|max:50',
            'model'             => 'required|string|max:50',
            'year'              => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'color'             => 'required|string|max:30',
            'type'              => 'required|string|max:30',
            'capacity_manual'   => 'required|integer|min:1|max:60',
            'vehicle_image_path'=> 'required|string', 
            'has_ac'            => 'required|boolean',

            // مسارات المستندات
            'doc_license_path'         => 'required|string',
            'doc_logbook_path'         => 'required|string',
            'doc_insurance_path'       => 'required|string',
            'doc_criminal_record_path' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.required'    => 'رقم الهوية الوطنية مطلوب.',
            'national_id.digits'      => 'الرقم الوطني يجب أن يتكون من 12 رقماً بالضبط.',
            'license_number.required' => 'رقم رخصة القيادة مطلوب.',
            'license_expiry.after'    => 'تاريخ انتهاء الرخصة يجب أن يكون في المستقبل.',
            'plate_number.required'   => 'رقم لوحة المركبة مطلوب.',
            'year.min'                => 'سنة صنع المركبة يجب أن تكون من عام 2000 فما فوق.',
            'year.max'                => 'سنة صنع المركبة غير منطقية.',
            'capacity_manual.min'     => 'سعة المركبة يجب أن تكون على الأقل مقعد واحد.',
            'capacity_manual.max'     => 'سعة الركاب القصوى المتاحة للتسجيل هي 60 راكباً.',
            'doc_license_path.required' => 'يرجى إرفاق صورة رخصة القيادة.',
            'doc_logbook_path.required' => 'يرجى إرفاق صورة دفتر المركبة.',
            'doc_insurance_path.required' => 'يرجى إرفاق صورة التأمين.',
            'doc_criminal_record_path.required' => 'يرجى إرفاق شهادة السجل الجنائي.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'بيانات إكمال الملف الشخصي غير مكتملة أو تحتوي على أخطاء.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
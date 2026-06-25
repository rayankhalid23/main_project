<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateVehicleDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_active !== 0;
    }

    public function rules(): array
    {
        // جلب معرف السيارة المبعوث في رابط الـ URL للتأكد من تجاهله في حقل اللوحة الفريد
        $vehicleId = $this->route('vehicle');

        return [
            'plate_number'    => ['sometimes', 'string', 'max:20', Rule::unique('vehicles', 'plate_number')->ignore($vehicleId)],
            'brand'           => ['sometimes', 'string', 'max:50'],
            'model'           => ['sometimes', 'string', 'max:50'],
            'year'            => ['sometimes', 'integer', 'digits:4', 'min:2000', 'max:' . (date('Y') + 1)],
            'color'           => ['sometimes', 'string', 'max:30'],
            'type'            => ['sometimes', 'in:Bus,Sedan,Van'],
            'capacity_manual' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'vehicle_image'   => ['sometimes', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
            'has_ac'          => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'plate_number.unique'   => 'رقم لوحة السيارة مسجل بالفعل لسيارة أخرى في المنظومة.',
            'year.min'              => 'يجب أن تكون سنة الصنع للمركبة من عام 2000 فما فوق.',
            'type.in'               => 'نوع السيارة يجب أن يكون حصراً من الفئات: Bus, Sedan, Van.',
            'capacity_manual.max'   => 'سعة الركاب القصوى المتاحة للتسجيل هي 60 راكباً.',
            'vehicle_image.image'   => 'يجب أن تكون صورة المركبة المرفوعة ملف صورة صالح.',
            'has_ac.boolean'        => 'حقل التكييف يجب أن يكون نعم أو لا فقط.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'بيانات تعديل المركبة غير مطابقة لشروط النظام.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
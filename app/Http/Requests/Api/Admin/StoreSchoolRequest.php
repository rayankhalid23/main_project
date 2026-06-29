<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    return [
        'name'     => 'required|string|max:150|unique:schools,name',
        'lat'      => 'required|numeric|between:-90,90',
        'lng'      => 'required|numeric|between:-180,180',
        'address'  => 'required|string|max:255',
        'zone_id'  => 'required|exists:zones,id' // التحقق من وجود المنطقة
    ];
}

public function messages(): array
{
    return [
        'name.required'     => 'اسم المدرسة حقل مطلوب.',
        'name.unique'       => 'اسم المدرسة مسجل مسبقاً في النظام.',
        'lat.required'      => 'إحداثيات خط العرض مطلوبة.',
        'lng.required'      => 'إحداثيات خط الطول مطلوبة.',
        'address.required'  => 'عنوان المدرسة التفصيلي مطلوب.',
        'zone_id.required'  => 'يجب اختيار المنطقة الجغرافية (البلدية) للمدرسة.',
        'zone_id.exists'    => 'المنطقة المختارة غير صالحة أو غير موجودة.'
    ];
}
}
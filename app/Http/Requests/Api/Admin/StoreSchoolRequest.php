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
            'name'         => 'required|string|max:150|unique:schools,name',
            'lat'          => 'required|numeric|between:-90,90',
            'lng'          => 'required|numeric|between:-180,180',
            'address_text' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'اسم المدرسة حقل مطلوب.',
            'name.unique'           => 'اسم المدرسة هذا مقترح أو مسجل في النظام مسبقاً.',
            'lat.required'          => 'إحداثيات خط العرض مطلوبة.',
            'lat.between'           => 'إحداثيات خط العرض غير صالحة جغرافياً.',
            'lng.required'          => 'إحداثيات خط الطول مطلوبة.',
            'lng.between'           => 'إحداثيات خط الطول غير صالحة جغرافياً.',
            'address_text.required' => 'الوصف النصي لعنوان المدرسة مطلوب.',
        ];
    }
}
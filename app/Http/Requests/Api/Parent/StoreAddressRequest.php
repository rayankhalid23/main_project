<?php

namespace App\Http\Requests\Api\Parent;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'      => 'required|string|max:100',
            'lat'        => 'required|numeric|between:-90,90',
            'lng'        => 'required|numeric|between:-180,180',
            'is_default' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'يرجى تحديد مسمى للعنوان (مثل: المنزل، بيت الجد).',
            'lat.required'   => 'إحداثيات خط العرض مطلوبة.',
            'lng.required'   => 'إحداثيات خط الطول مطلوبة.',
        ];
    }
}
<?php

namespace App\Http\Requests\Api\Driver;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // بيانات تعديل مباشر (لا تحتاج موافقة)
            'alternative_phone' => 'nullable|numeric|digits:10',
            'avatar'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // بيانات حساسة (تحتاج موافقة الإدارة للنجاح)
            'full_name'         => 'nullable|string|max:150',
            'national_id'       => 'nullable|numeric|digits:12',
            'license_number'    => 'nullable|string|max:50',
        ];
    }
}
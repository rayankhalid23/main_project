<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = $this->route('school') ? $this->route('school')->id : null;

        return [
            'name'         => 'sometimes|required|string|max:150|unique:schools,name,' . $schoolId,
            'lat'          => 'sometimes|required|numeric|between:-90,90',
            'lng'          => 'sometimes|required|numeric|between:-180,180',
            'address_text' => 'sometimes|required|string|max:255',
            'status'       => 'sometimes|required|in:approved,pending'
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'اسم المدرسة مستخدم بالفعل.',
            'status.in'   => 'الحالة الممررة غير صالحة، يجب أن تكون approved أو pending.',
        ];
    }
}
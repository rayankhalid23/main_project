<?php

namespace App\Http\Requests\Api\Shared;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:100', // لاحظ: حذفنا unique:users لأننا نتحكم في ذلك داخل الـ Controller
        ];
    }
}
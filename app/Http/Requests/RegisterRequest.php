<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // السماح للجميع بالوصول لهذا الطلب
    }

    public function rules(): array
    {
        return [
            'full_name'    => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'regex:/^09[0-9]{8}$/'],
            'password'     => ['required', 'string', 'min:6'],
            'otp'          => ['required', 'string', 'size:6'],
        ];
    }
}
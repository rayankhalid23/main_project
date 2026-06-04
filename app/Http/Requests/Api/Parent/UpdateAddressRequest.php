<?php

namespace App\Http\Requests\Api\Parent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'      => 'sometimes|required|string|max:100',
            'lat'        => 'sometimes|required|numeric|between:-90,90',
            'lng'        => 'sometimes|required|numeric|between:-180,180',
            'is_default' => 'nullable|boolean'
        ];
    }
}
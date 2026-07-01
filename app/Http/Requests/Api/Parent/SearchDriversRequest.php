<?php

namespace App\Http\Requests\Api\Parent;

use Illuminate\Foundation\Http\FormRequest;

class SearchDriversRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_query'  => 'nullable|string|max:100', // الاسم أو رقم الهاتف
            'driver_gender' => 'nullable|in:male,female',
            'has_ac'        => 'nullable|boolean',
            'child_ids'     => 'nullable|array',
            // التأكد أن الأطفال المحددين يتبعون فعلاً لولي الأمر الحالي
            'child_ids.*'   => 'exists:children,id,parent_id,' . auth()->id(), 
        ];
    }
}
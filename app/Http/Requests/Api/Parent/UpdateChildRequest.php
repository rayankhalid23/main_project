<?php

namespace App\Http\Requests\Api\Parent; 

class UpdateChildRequest extends StoreChildRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        
        // 1. تحويل جميع القواعد إلى 'sometimes'
        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                $rules[$field] = 'sometimes|' . str_replace('required|', '', $rule);
            } elseif (is_array($rule)) {
                // إذا كانت مصفوفة، نضمن وجود 'sometimes' ونحذف 'required' إذا وجدت
                $rules[$field] = array_merge(['sometimes'], array_diff($rule, ['required']));
            }
        }

        // 2. حماية حقل الـ QR Code (يُمنع تعديله تماماً)
        unset($rules['qr_code_token']);

        return $rules;
    }
}
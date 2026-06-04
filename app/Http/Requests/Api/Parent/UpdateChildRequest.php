<?php

namespace App\Http\Requests\Api\Parent; 

class UpdateChildRequest extends StoreChildRequest
{
    /**
     * في التعديل، نقوم بتعديل الشروط لتصبح متوافقة مع عملية التحديث (مثلاً جعل الحقول متواجدة فقط في حال إرسالها)
     */
    public function rules(): array
    {
        $rules = parent::rules();
        
        // تحويل القيود لتتماشى مع التحديث (أحياناً نكتفي بوضع 'sometimes' قبل القيود)
        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                $rules[$field] = 'sometimes|' . $rule;
            } elseif (is_array($rule)) {
                array_unshift($rules[$field], 'sometimes');
            }
        }

        return $rules;
    }
}
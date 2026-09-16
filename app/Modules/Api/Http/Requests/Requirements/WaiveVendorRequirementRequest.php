<?php

namespace App\Modules\Api\Http\Requests\Requirements;

use App\Modules\Api\Http\Requests\ApiRequest;

class WaiveVendorRequirementRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'سبب الاستثناء الإداري مطلوب.',
            'reason.string' => 'سبب الاستثناء الإداري يجب أن يكون نصاً صالحاً.',
            'reason.min' => 'سبب الاستثناء الإداري يجب ألا يقل عن 5 أحرف.',
            'reason.max' => 'سبب الاستثناء الإداري يجب ألا يتجاوز 1000 حرف.',
        ];
    }
}

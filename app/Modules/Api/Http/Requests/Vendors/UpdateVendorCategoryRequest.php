<?php

namespace App\Modules\Api\Http\Requests\Vendors;

use App\Modules\Api\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateVendorCategoryRequest extends ApiRequest
{
    public function rules(): array
    {
        $categoryId = $this->route('vendor_category') ?? $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('vendor_categories')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($categoryId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب.',
            'name.min' => 'اسم التصنيف يجب ألا يقل عن حرفين.',
            'name.string' => 'اسم التصنيف يجب أن يكون نصاً صالحاً.',
            'name.max' => 'اسم التصنيف يجب ألا يتجاوز 255 حرفاً.',
            'name.unique' => 'اسم التصنيف مسجل مسبقاً لدى منشأتكم.',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون قيمة منطقية.',
        ];
    }
}

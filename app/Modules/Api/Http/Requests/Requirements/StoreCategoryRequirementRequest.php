<?php

namespace App\Modules\Api\Http\Requests\Requirements;

use App\Modules\Api\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequirementRequest extends ApiRequest
{
    public function rules(): array
    {
        $category = $this->route('vendor_category');
        $categoryId = is_object($category) ? $category->id : $category;

        return [
            'document_type_id' => [
                'required',
                'uuid',
                Rule::exists('document_types', 'id')->where('tenant_id', $this->user()->tenant_id),
                Rule::unique('vendor_category_requirements')
                    ->where('vendor_category_id', $categoryId)
                    ->where('tenant_id', $this->user()->tenant_id),
            ],
            'is_mandatory' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type_id.required' => 'نوع الوثيقة مطلوب.',
            'document_type_id.uuid' => 'معرف نوع الوثيقة غير صالح.',
            'document_type_id.exists' => 'نوع الوثيقة المحدد غير موجود أو لا ينتمي لمنشأتكم.',
            'document_type_id.unique' => 'تمت إضافة هذه الوثيقة مسبقاً كمتطلب لهذا التصنيف.',
            'is_mandatory.boolean' => 'حالة الإلزامية يجب أن تكون قيمة منطقية.',
            'notes.string' => 'الملاحظات يجب أن تكون نصاً صالحاً.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
        ];
    }
}

<?php

namespace App\Modules\Api\Http\Requests\Requirements;

use App\Modules\Api\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequirementRequest extends ApiRequest
{
    public function rules(): array
    {
        $vendor = $this->route('vendor');
        $vendorId = is_object($vendor) ? $vendor->id : $vendor;

        return [
            'document_type_id' => [
                'required',
                'uuid',
                Rule::exists('document_types', 'id')->where('tenant_id', $this->user()->tenant_id),
                Rule::unique('vendor_requirements')
                    ->where('vendor_id', $vendorId)
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
            'document_type_id.unique' => 'تمت إضافة هذه الوثيقة مسبقاً لهذا المورد.',
            'is_mandatory.boolean' => 'حالة الإلزامية يجب أن تكون قيمة منطقية.',
            'notes.string' => 'الملاحظات يجب أن تكون نصاً صالحاً.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
        ];
    }
}

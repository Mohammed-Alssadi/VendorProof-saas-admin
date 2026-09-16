<?php

namespace App\Modules\Api\Http\Requests\Requirements;

use App\Modules\Api\Http\Requests\ApiRequest;
use App\Modules\Api\Models\VendorRequirement;
use Illuminate\Validation\Rule;

class UpdateVendorRequirementRequest extends ApiRequest
{
    public function rules(): array
    {
        $requirement = $this->route('vendor_requirement');
        $requirementId = is_object($requirement) ? $requirement->id : $requirement;
        $vendorId = is_object($requirement)
            ? $requirement->vendor_id
            : VendorRequirement::where('id', $requirementId)->value('vendor_id');

        return [
            'document_type_id' => [
                'sometimes',
                'required',
                'uuid',
                Rule::exists('document_types', 'id')->where('tenant_id', $this->user()->tenant_id),
                Rule::unique('vendor_requirements')
                    ->where('vendor_id', $vendorId)
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($requirementId),
            ],
            'is_mandatory' => ['sometimes', 'required', 'boolean'],
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
            'is_mandatory.required' => 'حالة الإلزامية مطلوبة.',
            'is_mandatory.boolean' => 'حالة الإلزامية يجب أن تكون قيمة منطقية.',
            'notes.string' => 'الملاحظات يجب أن تكون نصاً صالحاً.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
        ];
    }
}

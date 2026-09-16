<?php

namespace App\Modules\Api\Http\Requests\Documents;

use App\Modules\Api\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentTypeRequest extends ApiRequest
{
    public function rules(): array
    {
        $documentType = $this->route('document_type') ?? $this->route('id');
        $id = is_object($documentType) ? $documentType->id : $documentType;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('document_types')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($id),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-Z0-9_\-]+$/',
                Rule::unique('document_types')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($id),
            ],
            'description' => ['nullable', 'string'],
            'requires_expiry' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم نوع الوثيقة مطلوب.',
            'name.min' => 'اسم نوع الوثيقة يجب ألا يقل عن حرفين.',
            'name.string' => 'اسم نوع الوثيقة يجب أن يكون نصاً صالحاً.',
            'name.max' => 'اسم نوع الوثيقة يجب ألا يتجاوز 255 حرفاً.',
            'name.unique' => 'اسم نوع الوثيقة مسجل مسبقاً لدى منشأتكم.',
            'code.required' => 'كود نوع الوثيقة مطلوب.',
            'code.min' => 'كود نوع الوثيقة يجب ألا يقل عن حرفين.',
            'code.max' => 'كود نوع الوثيقة يجب ألا يتجاوز 50 حرفاً.',
            'code.regex' => 'كود نوع الوثيقة يجب أن يحتوي فقط على أحرف وأرقام إنجليزية وشرطات.',
            'code.unique' => 'كود نوع الوثيقة مسجل مسبقاً لدى منشأتكم.',
            'requires_expiry.boolean' => 'قيمة إلزامية تاريخ الانتهاء يجب أن تكون منطقية.',
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون قيمة منطقية.',
        ];
    }
}

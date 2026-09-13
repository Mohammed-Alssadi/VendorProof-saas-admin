<?php

namespace App\Modules\Api\Http\Requests\Company;

use App\Modules\Api\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * Validate company and tenant profile updates.
 */
class UpdateCompanyProfileRequest extends ApiRequest
{

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'name'       => ['required', 'string', 'max:255'],
            'company_cr' => ['nullable', 'string', 'max:20', Rule::unique('tenants', 'company_cr')->ignore($tenantId)],
            'email'      => ['required', 'email', 'max:255', Rule::unique('tenants', 'email')->ignore($tenantId)],
            'phone'      => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'اسم المنشأة مطلوب.',
            'name.max'           => 'اسم المنشأة يجب ألا يتجاوز 255 حرفاً.',
            'company_cr.max'     => 'رقم السجل التجاري يجب ألا يتجاوز 20 رقماً.',
            'company_cr.unique'  => 'رقم السجل التجاري مسجل مسبقاً لمنشأة أخرى.',
            'email.required'     => 'البريد الإلكتروني الرسمي للمنشأة مطلوب.',
            'email.email'        => 'صيغة البريد الإلكتروني غير صالحة.',
            'email.unique'       => 'البريد الإلكتروني مسجل مسبقاً لمنشأة أخرى.',
            'phone.max'          => 'رقم الهاتف يجب ألا يتجاوز 20 رقماً.',
        ];
    }
}

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
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/[\pL]/u', 'not_regex:/^[0-9]+$/'],
            'company_cr' => ['nullable', 'numeric', 'digits_between:10,20', Rule::unique('tenants', 'company_cr')->ignore($tenantId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'email')->ignore($tenantId)],
            'phone' => ['nullable', 'string', 'regex:/^(\+?[0-9\s\-]{7,20})$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المنشأة مطلوب.',
            'name.min' => 'اسم المنشأة يجب ألا يقل عن حرفين.',
            'name.regex' => 'اسم المنشأة يجب أن يحتوي على أحرف صالحة.',
            'name.not_regex' => 'اسم المنشأة لا يمكن أن يتكون من أرقام فقط.',
            'name.max' => 'اسم المنشأة يجب ألا يتجاوز 255 حرفاً.',
            'company_cr.numeric' => 'رقم السجل التجاري يجب أن يحتوي على أرقام فقط.',
            'company_cr.digits_between' => 'رقم السجل التجاري يجب أن يتكون من 10 إلى 20 رقماً.',
            'company_cr.unique' => 'رقم السجل التجاري مسجل مسبقاً لمنشأة أخرى.',
            'email.required' => 'البريد الإلكتروني الرسمي للمنشأة مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صالحة.',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لمنشأة أخرى.',
            'phone.regex' => 'رقم الهاتف غير صالح، يجب أن يتكون من أرقام صحيحة (بين 7 و 20 رقماً).',
        ];
    }
}

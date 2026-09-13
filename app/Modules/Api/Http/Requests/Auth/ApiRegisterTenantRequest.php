<?php

namespace App\Modules\Api\Http\Requests\Auth;

use App\Modules\Api\Http\Requests\ApiRequest;

/**
 * Validate self-service tenant registration and administrator setup.
 */
class ApiRegisterTenantRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            'company_name'  => ['required', 'string', 'max:255'],
            'slug'          => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                'unique:tenants,slug',
            ],
            'company_cr'    => ['nullable', 'string', 'max:20', 'unique:tenants,company_cr'],
            'company_email' => ['nullable', 'email', 'max:255', 'unique:tenants,email'],
            'phone'         => ['nullable', 'string', 'max:20'],

            'name'          => ['required', 'string', 'max:255'],
            'email'         => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                $this->filled('company_email') ? 'nullable' : 'unique:tenants,email',
            ],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'اسم المنشأة مطلوب.',
            'slug.required'         => 'رابط المنشأة مطلوب.',
            'slug.regex'            => 'الرابط يجب أن يحتوي على أحرف إنجليزية صغيرة وأرقام وشرطة (-) فقط.',
            'slug.unique'           => 'هذا الرابط مستخدم مسبقاً، يرجى اختيار رابط آخر.',
            'company_cr.unique'     => 'رقم السجل التجاري مسجل مسبقاً.',
            'company_email.email'   => 'بريد المنشأة الإلكتروني غير صالح.',
            'company_email.unique'  => 'بريد المنشأة الإلكتروني مسجل مسبقاً لمنشأة أخرى.',
            'name.required'         => 'الاسم مطلوب.',
            'email.required'        => 'البريد الإلكتروني مطلوب.',
            'email.email'           => 'البريد الإلكتروني غير صالح.',
            'email.unique'          => 'البريد الإلكتروني مسجل مسبقاً.',
            'password.required'     => 'كلمة المرور مطلوبة.',
            'password.min'          => 'كلمة المرور يجب ألا تقل عن 8 خانات.',
            'password.confirmed'    => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}

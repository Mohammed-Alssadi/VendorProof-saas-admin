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
            'company_name'  => ['required', 'string', 'min:2', 'max:255', 'regex:/[\pL]/u', 'not_regex:/^[0-9]+$/'],
            'slug'          => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                'unique:tenants,slug',
            ],
            'company_cr'    => ['nullable', 'numeric', 'digits_between:10,20', 'unique:tenants,company_cr'],
            'company_email' => ['nullable', 'email', 'max:255', 'unique:tenants,email'],
            'phone'         => ['nullable', 'string', 'regex:/^(\+?[0-9\s\-]{7,20})$/'],

            'name'          => ['required', 'string', 'min:2', 'max:255', 'regex:/[\pL]/u', 'not_regex:/^[0-9]+$/'],
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
            'company_name.required'     => 'اسم المنشأة مطلوب.',
            'company_name.min'          => 'اسم المنشأة يجب ألا يقل عن حرفين.',
            'company_name.regex'        => 'اسم المنشأة يجب أن يحتوي على أحرف صالحة.',
            'company_name.not_regex'    => 'اسم المنشأة لا يمكن أن يتكون من أرقام فقط.',
            'slug.required'             => 'رابط المنشأة مطلوب.',
            'slug.min'                  => 'رابط المنشأة يجب ألا يقل عن 3 أحرف.',
            'slug.regex'                => 'الرابط يجب أن يحتوي على أحرف إنجليزية صغيرة وأرقام وشرطة (-) فقط.',
            'slug.unique'               => 'هذا الرابط مستخدم مسبقاً، يرجى اختيار رابط آخر.',
            'company_cr.numeric'        => 'رقم السجل التجاري يجب أن يحتوي على أرقام فقط.',
            'company_cr.digits_between' => 'رقم السجل التجاري يجب أن يتكون من 10 إلى 20 رقماً.',
            'company_cr.unique'         => 'رقم السجل التجاري مسجل مسبقاً.',
            'company_email.email'       => 'بريد المنشأة الإلكتروني غير صالح.',
            'company_email.unique'      => 'بريد المنشأة الإلكتروني مسجل مسبقاً لمنشأة أخرى.',
            'phone.regex'               => 'رقم الهاتف غير صالح، يجب أن يتكون من أرقام صحيحة (بين 7 و 20 رقماً).',
            'name.required'             => 'الاسم مطلوب.',
            'name.min'                  => 'الاسم يجب ألا يقل عن حرفين.',
            'name.regex'                => 'الاسم يجب أن يحتوي على أحرف صالحة.',
            'name.not_regex'            => 'الاسم لا يمكن أن يتكون من أرقام فقط.',
            'email.required'            => 'البريد الإلكتروني مطلوب.',
            'email.email'               => 'البريد الإلكتروني غير صالح.',
            'email.unique'              => 'البريد الإلكتروني مسجل مسبقاً.',
            'password.required'         => 'كلمة المرور مطلوبة.',
            'password.min'              => 'كلمة المرور يجب ألا تقل عن 8 خانات.',
            'password.confirmed'        => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}

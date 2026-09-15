<?php

namespace App\Modules\Web\Http\Requests\Tenants;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * فحص وتحقق مدخلات تعديل بيانات المنشأة وباقة اشتراكها من لوحة الإدارة.
 */
class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id ?? $this->route('tenant');

        return [
            // 1. بيانات المنشأة والسجل التجاري
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/[\pL]/u', 'not_regex:/^[0-9]+$/'],
            'slug' => [
                'nullable',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('tenants', 'slug')->ignore($tenantId),
            ],
            'company_cr' => [
                'required',
                'numeric',
                'digits_between:10,20',
                Rule::unique('tenants', 'company_cr')->ignore($tenantId),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('tenants', 'email')->ignore($tenantId),
            ],
            'phone' => ['required', 'string', 'regex:/^(\+?[0-9\s\-]{7,20})$/'],

            // 2. باقة المنشأة والحالة التشغيلية ودورة الفوترة
            'plan_id'       => ['required', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
            'status'        => ['required', 'in:active,suspended'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال اسم المنشأة أو الشركة الرسمي.',
            'name.min' => 'اسم المنشأة يجب ألا يقل عن حرفين.',
            'name.regex' => 'اسم المنشأة يجب أن يحتوي على أحرف صالحة.',
            'name.not_regex' => 'اسم المنشأة لا يمكن أن يتكون من أرقام فقط.',
            'name.max' => 'اسم المنشأة يجب ألا يتجاوز 255 حرفاً.',

            'slug.min' => 'رابط المنشأة يجب ألا يقل عن 3 أحرف.',
            'slug.regex' => 'الرابط يجب أن يحتوي على أحرف إنجليزية صغيرة وأرقام وشرطة (-) فقط.',
            'slug.unique' => 'هذا الرابط مستخدم مسبقاً، يرجى اختيار رابط آخر.',

            'company_cr.required' => 'يرجى إدخال رقم السجل التجاري المعتمد.',
            'company_cr.numeric' => 'رقم السجل التجاري يجب أن يحتوي على أرقام فقط.',
            'company_cr.digits_between' => 'رقم السجل التجاري يجب أن يتكون من 10 إلى 20 رقماً.',
            'company_cr.unique' => 'رقم السجل التجاري مسجل مسبقاً لمنشأة أخرى.',

            'email.required' => 'يرجى إدخال البريد الإلكتروني الرسمي للمنشأة.',
            'email.email' => 'البريد الإلكتروني للمنشأة غير صالح.',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لمنشأة أخرى.',

            'phone.required' => 'يرجى إدخال رقم الهاتف المعتمد للمنشأة.',
            'phone.regex' => 'رقم الهاتف غير صالح، يجب أن يتكون من أرقام صحيحة (بين 7 و 20 رقماً).',

            'plan_id.required' => 'يرجى اختيار باقة الاشتراك للمنشأة.',
            'plan_id.exists' => 'باقة الاشتراك المحددة غير موجودة في النظام.',

            'billing_cycle.required' => 'يرجى تحديد دورة الفوترة (شهري أو سنوي).',
            'billing_cycle.in' => 'دورة الفوترة المحددة غير صالحة.',

            'status.required' => 'يرجى تحديد حالة المنشأة التشغيلية.',
            'status.in' => 'حالة المنشأة المحددة غير صالحة.',
        ];
    }
}

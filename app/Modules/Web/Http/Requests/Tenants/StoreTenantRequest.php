<?php

namespace App\Modules\Web\Http\Requests\Tenants;

use Illuminate\Foundation\Http\FormRequest;

/**
 * فحص وتحقق مدخلات تسجيل منشأة جديدة وتعيين مديرها واشتراكها من لوحة الإدارة.
 */
class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 1. بيانات المنشأة والسجل التجاري
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/[\pL]/u', 'not_regex:/^[0-9]+$/'],
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                'unique:tenants,slug',
            ],
            'company_cr' => ['required', 'numeric', 'digits_between:10,20', 'unique:tenants,company_cr'],
            'email' => ['required', 'email', 'max:255', 'unique:tenants,email'],
            'phone' => ['required', 'string', 'regex:/^(\+?[0-9\s\-]{7,20})$/'],

            // 2. بيانات حساب مدير المنشأة الأولي (حساب الدخول)
            'admin_name' => ['required', 'string', 'min:2', 'max:255', 'regex:/[\pL]/u', 'not_regex:/^[0-9]+$/'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],

            // 3. باقة الاشتراك ودورة الفوترة
            'plan_id'       => ['nullable', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
        ];
    }

    public function messages(): array
    {
        return [
            // بيانات المنشأة
            'name.required' => 'يرجى إدخال اسم المنشأة أو الشركة الرسمي.',
            'name.min' => 'اسم المنشأة يجب ألا يقل عن حرفين.',
            'name.regex' => 'اسم المنشأة يجب أن يحتوي على أحرف صالحة.',
            'name.not_regex' => 'اسم المنشأة لا يمكن أن يتكون من أرقام فقط.',
            'name.max' => 'اسم المنشأة يجب ألا يتجاوز 255 حرفاً.',

            'slug.required' => 'رابط المنشأة مطلوب.',
            'slug.min' => 'رابط المنشأة يجب ألا يقل عن 3 أحرف.',
            'slug.string' => 'رابط المنشأة غير صالح.',
            'slug.max' => 'رابط المنشأة يجب ألا يتجاوز 100 حرف.',
            'slug.regex' => 'الرابط يجب أن يحتوي على أحرف إنجليزية صغيرة وأرقام وشرطة (-) فقط (مثال: sahab-tech).',
            'slug.unique' => 'هذا الرابط مستخدم مسبقاً، يرجى اختيار رابط آخر.',

            'company_cr.required' => 'يرجى إدخال رقم السجل التجاري المعتمد.',
            'company_cr.numeric' => 'رقم السجل التجاري يجب أن يحتوي على أرقام فقط.',
            'company_cr.digits_between' => 'رقم السجل التجاري يجب أن يتكون من 10 إلى 20 رقماً.',
            'company_cr.unique' => 'رقم السجل التجاري مسجل مسبقاً لمنشأة أخرى.',

            'email.required' => 'يرجى إدخال البريد الإلكتروني الرسمي للمنشأة.',
            'email.email' => 'البريد الإلكتروني للمنشأة غير صالح.',
            'email.unique' => 'البريد الإلكتروني للمنشأة مسجل مسبقاً.',

            'phone.required' => 'يرجى إدخال رقم الهاتف المعتمد للمنشأة.',
            'phone.regex' => 'رقم الهاتف غير صالح، يجب أن يتكون من أرقام صحيحة (بين 7 و 20 رقماً).',

            // بيانات المدير
            'admin_name.required' => 'يرجى إدخال الاسم الكامل لمدير المنشأة.',
            'admin_name.min' => 'اسم المدير يجب ألا يقل عن حرفين.',
            'admin_name.regex' => 'اسم المدير يجب أن يحتوي على أحرف صالحة.',
            'admin_name.not_regex' => 'اسم المدير لا يمكن أن يتكون من أرقام فقط.',
            'admin_name.max' => 'اسم المدير يجب ألا يتجاوز 255 حرفاً.',

            'admin_email.required' => 'يرجى إدخال البريد الإلكتروني لتسجيل دخول مدير المنشأة.',
            'admin_email.email' => 'بريد مدير المنشأة غير صالح.',
            'admin_email.unique' => 'بريد المدير مستخدم بالفعل لحساب آخر في النظام.',

            'admin_password.required' => 'يرجى تحديد كلمة المرور الأولية لحساب المدير.',
            'admin_password.min' => 'كلمة المرور يجب ألا تقل عن 8 خانات لضمان الأمان.',

            // بيانات الاشتراك
            'plan_id.required' => 'يرجى اختيار باقة الاشتراك للمنشأة.',
            'plan_id.exists' => 'باقة الاشتراك المحددة غير موجودة في النظام.',

            'billing_cycle.required' => 'يرجى تحديد دورة الفوترة (شهري أو سنوي).',
            'billing_cycle.in' => 'دورة الفوترة المحددة غير صالحة.',
        ];
    }
}

<?php

namespace App\Modules\Web\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation request for admin account registration.
 */
class AdminRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/[\pL]/u', 'not_regex:/^[0-9]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'حقل الاسم مطلوب.',
            'name.min' => 'حقل الاسم يجب ألا يقل عن حرفين.',
            'name.regex' => 'حقل الاسم يجب أن يحتوي على أحرف صالحة.',
            'name.not_regex' => 'حقل الاسم لا يمكن أن يتكون من أرقام فقط.',
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صالح.',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لمسؤول آخر.',
            'password.required' => 'حقل كلمة المرور مطلوب.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 6 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}

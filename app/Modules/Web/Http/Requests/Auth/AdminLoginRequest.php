<?php

namespace App\Modules\Web\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation request for admin authentication.
 */
class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صالح.',
            'password.required' => 'حقل كلمة المرور مطلوب.',
        ];
    }
}

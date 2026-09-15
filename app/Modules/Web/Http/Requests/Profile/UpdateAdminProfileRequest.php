<?php

namespace App\Modules\Web\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateAdminProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $adminId = Auth::id();

        return [
            'name'  => ['required', 'string', 'min:2', 'max:255', 'regex:/[\pL]/u', 'not_regex:/^[0-9]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email,' . $adminId],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'حقل الاسم مطلوب.',
            'name.min'       => 'حقل الاسم يجب ألا يقل عن حرفين.',
            'name.regex'     => 'حقل الاسم يجب أن يحتوي على أحرف صالحة.',
            'name.not_regex' => 'حقل الاسم لا يمكن أن يتكون من أرقام فقط.',
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email'    => 'يرجى إدخال بريد إلكتروني صالح.',
            'email.unique'   => 'البريد الإلكتروني مسجل مسبقاً لمشرف آخر.',
        ];
    }
}

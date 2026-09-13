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
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email,' . $adminId],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'حقل الاسم مطلوب.',
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email'    => 'يرجى إدخال بريد إلكتروني صالح.',
            'email.unique'   => 'البريد الإلكتروني مسجل مسبقاً لمشرف آخر.',
        ];
    }
}

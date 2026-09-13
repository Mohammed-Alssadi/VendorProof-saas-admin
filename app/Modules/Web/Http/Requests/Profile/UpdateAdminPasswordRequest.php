<?php

namespace App\Modules\Web\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateAdminPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'         => 'يرجى إدخال كلمة المرور الحالية.',
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.required'                 => 'يرجى إدخال كلمة المرور الجديدة.',
            'password.min'                      => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.confirmed'                => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}

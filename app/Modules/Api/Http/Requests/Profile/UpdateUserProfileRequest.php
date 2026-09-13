<?php

namespace App\Modules\Api\Http\Requests\Profile;

use App\Modules\Api\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * Validate authenticated user profile and security credentials update.
 */
class UpdateUserProfileRequest extends ApiRequest
{

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                    => 'الاسم مطلوب.',
            'name.max'                         => 'الاسم يجب ألا يتجاوز 255 حرفاً.',
            'email.required'                   => 'البريد الإلكتروني مطلوب.',
            'email.email'                      => 'صيغة البريد الإلكتروني غير صالحة.',
            'email.unique'                     => 'البريد الإلكتروني مسجل مسبقاً لمستخدم آخر.',
            'current_password.required_with'   => 'كلمة المرور الحالية مطلوبة لتأكيد تغيير كلمة المرور.',
            'password.min'                     => 'كلمة المرور الجديدة يجب ألا تقل عن 8 خانات.',
            'password.confirmed'               => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}

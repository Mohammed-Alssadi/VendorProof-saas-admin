<?php

namespace App\Modules\Api\Http\Requests\Company;

use App\Modules\Api\Http\Requests\ApiRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Hash;

/**
 * Validate company account termination and credentials verification.
 */
class CloseCompanyAccountRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'confirmation_name' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة لتأكيد إغلاق المنشأة.',
            'confirmation_name.required' => 'يرجى إدخال اسم المنشأة الصريح لتأكيد عملية الإغلاق.',
        ];
    }

    /**
     * Configure additional validator checks for password and confirmation name.
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $tenant = $user?->tenant;

            // 1. التحقق من صحة كلمة المرور الحالية
            if ($this->filled('current_password') && ! Hash::check($this->current_password, $user?->password)) {
                $validator->errors()->add('current_password', 'كلمة المرور الحالية غير مطابقة لسجلاتنا.');
            }

            // 2. التحقق من تطابق اسم المنشأة الصريح لمنع الحذف بالخطأ
            if ($tenant && $this->filled('confirmation_name') && trim($this->confirmation_name) !== trim($tenant->name)) {
                $validator->errors()->add('confirmation_name', 'اسم المنشأة المدخل للتأكيد غير مطابق للاسم الرسمي.');
            }
        });
    }
}

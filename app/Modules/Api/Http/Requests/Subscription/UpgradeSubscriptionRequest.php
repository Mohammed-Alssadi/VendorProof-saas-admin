<?php

namespace App\Modules\Api\Http\Requests\Subscription;

use App\Modules\Api\Http\Requests\ApiRequest;

/**
 * Validate tenant subscription upgrade request.
 */
class UpgradeSubscriptionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'plan_id'       => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'string', 'in:monthly,yearly'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required'    => 'معرف الباقة مطلوب.',
            'plan_id.integer'     => 'معرف الباقة يجب أن يكون رقماً صحيحاً.',
            'plan_id.exists'      => 'الباقة المختارة غير موجودة.',
            'billing_cycle.in'    => 'دورة الفوترة يجب أن تكون إما شهرية (monthly) أو سنوية (yearly).',
        ];
    }
}

<?php

namespace App\Modules\Api\Http\Requests\Subscription;

use App\Modules\Api\Http\Requests\ApiRequest;

class CheckoutSubscriptionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'string', 'in:monthly,yearly'],
            'success_url' => ['nullable', 'string', 'url'],
            'cancel_url' => ['nullable', 'string', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'معرف الباقة المراد الترقية إليها مطلوب.',
            'plan_id.integer' => 'معرف الباقة يجب أن يكون رقماً صحيحاً.',
            'plan_id.exists' => 'الباقة المختارة غير متوفرة في النظام.',
            'billing_cycle.in' => 'دورة الفوترة يجب أن تكون إما شهرية (monthly) أو سنوية (yearly).',
            'success_url.url' => 'رابط العودة عند النجاح يجب أن يكون رابطاً صالحاً (URL).',
            'cancel_url.url' => 'رابط العودة عند الإلغاء يجب أن يكون رابطاً صالحاً (URL).',
        ];
    }
}

<?php

namespace App\Modules\Web\Http\Requests\Plans;

use Illuminate\Foundation\Http\FormRequest;

/**
 * فحص وتحقق بيانات تحديث ميزات وحدود باقة الاشتراك.
 */
class UpdatePlanFeaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'array'],
            'features.*.value' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'features.array' => 'بيانات الميزات والحدود يجب أن تكون مصفوفة صالحة.',
        ];
    }
}

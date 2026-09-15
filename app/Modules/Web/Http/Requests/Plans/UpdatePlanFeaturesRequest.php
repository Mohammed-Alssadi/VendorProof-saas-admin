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
            'features.*.value' => ['nullable', 'numeric', 'min:-1'],
        ];
    }

    public function messages(): array
    {
        return [
            'features.array' => 'بيانات الميزات والحدود يجب أن تكون مصفوفة صالحة.',
            'features.*.value.numeric' => 'قيمة الميزة أو الحد التشغيلي يجب أن تكون قيمة رقمية صالحة.',
            'features.*.value.min' => 'قيمة الحد التشغيلي لا يمكن أن تقل عن -1 (لغير المحدود).',
        ];
    }
}

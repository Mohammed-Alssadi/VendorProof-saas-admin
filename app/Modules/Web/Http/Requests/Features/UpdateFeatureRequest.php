<?php

namespace App\Modules\Web\Http\Requests\Features;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for validating feature and limit updates.
 */
class UpdateFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $feature = $this->route('feature');
        $featureId = is_object($feature) ? $feature->id : $feature;

        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('features', 'key')->ignore($featureId),
            ],
            'type' => ['required', 'in:feature,limit'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال اسم الميزة بالعربية.',
            'name.string' => 'اسم الميزة يجب أن يكون نصاً صالحاً.',
            'name.max' => 'اسم الميزة لا يجب أن يتجاوز 255 حرفاً.',
            'key.required' => 'اسم الميزة بالإنجليزية مطلوب.',
            'key.string' => 'اسم الميزة بالإنجليزية غير صالح.',
            'key.max' => 'اسم الميزة بالإنجليزية يجب ألا يتجاوز 100 حرف.',
            'key.regex' => 'اسم الميزة يجب أن يحتوي على أحرف إنجليزية صغيرة وأرقام وشرطة سفلية (_) فقط (مثال: max_vendors).',
            'key.unique' => 'هذا الاسم مستخدم مسبقاً لميزة أخرى.',
            'type.required' => 'يرجى تحديد نوع الميزة التشغيلية.',
            'type.in' => 'نوع الميزة المختار غير صالح (يجب أن يكون حد عددي أو ميزة تشغيلية).',
        ];
    }
}

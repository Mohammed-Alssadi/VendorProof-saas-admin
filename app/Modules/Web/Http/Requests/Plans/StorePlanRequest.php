<?php

namespace App\Modules\Web\Http\Requests\Plans;

use Illuminate\Foundation\Http\FormRequest;

/**
 * فحص وتحقق بيانات إنشاء باقة اشتراك جديدة وأسعارها وميزاتها.
 */
class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 1. البيانات الأساسية للباقة
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                'unique:plans,slug',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive'],

            // 2. أسعار الفوترة والعملة
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'yearly_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'in:SAR,USD,EUR,AED'],

            // 3. مصفوفة الحدود والميزات التشغيلية بالصيغة المباشرة
            'features' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال اسم باقة الاشتراك بالعربية.',
            'name.string' => 'اسم الباقة يجب أن يكون نصاً صالحاً.',
            'name.max' => 'اسم الباقة يجب ألا يتجاوز 255 حرفاً.',

            'slug.required' => 'الاسم التعريفي للباقة بالإنجليزية مطلوب.',
            'slug.string' => 'الاسم التعريفي للباقة غير صالح.',
            'slug.max' => 'الاسم التعريفي للباقة يجب ألا يتجاوز 100 حرف.',
            'slug.regex' => 'الاسم التعريفي يجب أن يتكون من أحرف إنجليزية صغيرة وأرقام وشرطة (-) فقط (مثال: pro-plan).',
            'slug.unique' => 'هذا الاسم مستخدم مسبقاً لباقة أخرى.',

            'description.string' => 'الوصف التشغيلي للباقة يجب أن يكون نصاً صالحاً.',
            'description.max' => 'وصف الباقة يجب ألا يتجاوز 1000 حرف.',

            'status.required' => 'يرجى تحديد حالة إتاحة الباقة للاشتراك.',
            'status.in' => 'حالة الباقة المحددة غير صالحة.',

            'monthly_price.required' => 'يرجى تحديد السعر الشهري للباقة.',
            'monthly_price.numeric' => 'السعر الشهري يجب أن يكون قيمة رقمية صالحة.',
            'monthly_price.min' => 'السعر الشهري لا يمكن أن يكون سالباً.',

            'yearly_price.required' => 'يرجى تحديد السعر السنوي للباقة.',
            'yearly_price.numeric' => 'السعر السنوي يجب أن يكون قيمة رقمية صالحة.',
            'yearly_price.min' => 'السعر السنوي لا يمكن أن يكون سالباً.',

            'currency.required' => 'يرجى تحديد عملة التسعير المعتمدة.',
            'currency.in' => 'العملة المحددة غير مدعومة حالياً.',
        ];
    }
}

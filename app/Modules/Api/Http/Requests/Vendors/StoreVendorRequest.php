<?php

namespace App\Modules\Api\Http\Requests\Vendors;

use App\Modules\Api\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/[\pL]/u',
            ],
            'contact_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/[\pL]/u',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('vendors', 'email')->where('tenant_id', $this->user()->tenant_id),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^(\+?[0-9\s\-]{7,20})$/',
            ],
            'vendor_category_id' => [
                'nullable',
                'integer',
                Rule::exists('vendor_categories', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(['active', 'pending', 'suspended']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'اسم المنشأة / المورد مطلوب.',
            'name.string'               => 'اسم المورد يجب أن يكون نصاً صالحاً.',
            'name.min'                  => 'اسم المورد يجب ألا يقل عن حرفين.',
            'name.max'                  => 'اسم المورد يجب ألا يتجاوز 255 حرفاً.',
            'name.regex'                => 'اسم المورد يجب أن يحتوي على أحرف حقيقية ولا يمكن أن يكون أرقاماً فقط.',
            'contact_name.required'     => 'اسم مسؤول التواصل مطلوب.',
            'contact_name.string'       => 'اسم مسؤول التواصل يجب أن يكون نصاً صالحاً.',
            'contact_name.min'          => 'اسم مسؤول التواصل يجب ألا يقل عن حرفين.',
            'contact_name.max'          => 'اسم مسؤول التواصل يجب ألا يتجاوز 255 حرفاً.',
            'contact_name.regex'        => 'اسم مسؤول التواصل يجب أن يحتوي على أحرف حقيقية ولا يمكن أن يكون أرقاماً فقط.',
            'email.required'            => 'البريد الإلكتروني للمورد مطلوب.',
            'email.email'               => 'يرجى إدخال بريد إلكتروني صالح.',
            'email.max'                 => 'البريد الإلكتروني يجب ألا يتجاوز 255 حرفاً.',
            'email.unique'              => 'البريد الإلكتروني مسجل مسبقاً لمورد آخر لدى منشأتكم.',
            'phone.regex'               => 'رقم الهاتف غير صالح، يجب أن يكون بتنسيق أرقام صالح من 7 إلى 20 رقماً.',
            'vendor_category_id.exists' => 'التصنيف المختار غير موجود أو لا يتبع منشأتكم.',
            'status.in'                 => 'حالة المورد غير صالحة، الحالات المتاحة هي (active, pending, suspended).',
        ];
    }
}

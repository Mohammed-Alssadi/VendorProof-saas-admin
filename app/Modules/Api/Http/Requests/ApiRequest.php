<?php

namespace App\Modules\Api\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * الكلاس الأساسي التجريدي لكافة طلبات التحقق للواجهات البرمجية (API Form Requests).
 *
 * يوفر معالجة معيارية موحدة لأخطاء التحقق وإرجاع استجابة JSON موحدة بهيكل الخطأ المعتمد.
 */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * إرجاع أخطاء التحقق بصيغة JSON نظيفة وواضحة للواجهات الخارجية.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'البيانات المدخلة غير صالحة أو غير مكتملة.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}

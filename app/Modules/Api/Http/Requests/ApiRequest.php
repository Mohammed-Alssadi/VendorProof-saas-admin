<?php

namespace App\Modules\Api\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation errors as a standardized JSON envelope instead of a redirect.
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

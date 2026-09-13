<?php

namespace App\Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * المتحكم الأساسي لبيئة الـ API.
 */
abstract class ApiController
{
    /**
     * استجابة نجاح موحدة
     */
    protected function successResponse($data = null, string $message = 'تمت العملية بنجاح', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * استجابة خطأ موحدة
     */
    protected function errorResponse(string $message = 'حدث خطأ ما', int $status = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}

<?php

namespace App\Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class ApiController
{
    protected function successResponse($data = null, string $message = 'تمت العملية بنجاح', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function errorResponse(string $message = 'حدث خطأ ما', int $status = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}

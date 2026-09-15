<?php

use App\Modules\Api\Http\Controllers\Auth\ApiAuthController;
use App\Modules\Api\Http\Controllers\Auth\ApiTenantRegistrationController;
use App\Modules\Api\Http\Controllers\Company\ApiCompanyController;
use App\Modules\Api\Http\Controllers\Plans\ApiPlanController;
use App\Modules\Api\Http\Controllers\Profile\ApiProfileController;
use App\Modules\Api\Http\Controllers\Subscription\ApiSubscriptionController;
use App\Modules\Api\Http\Controllers\Vendors\ApiVendorCategoryController;
use App\Modules\Api\Http\Controllers\Vendors\ApiVendorController;
use App\Modules\Api\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Headless Client & Tenant Core Backend)
|--------------------------------------------------------------------------
*/

Route::get('/ping', function () {
    return response()->json([
        'status'  => 'ok',
        'message' => 'VendorProof Platform API is active and healthy',
    ]);
});

Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('/plans', [ApiPlanController::class, 'index']);

    // Stripe webhook — public but signature-protected
    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

    // Guest auth — rate-limited
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [ApiTenantRegistrationController::class, 'register']);
        Route::post('/login', [ApiAuthController::class, 'login']);
    });

    // Sanctum-protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [ApiAuthController::class, 'me']);
        Route::post('/logout', [ApiAuthController::class, 'logout']);

        Route::get('/profile', [ApiProfileController::class, 'show']);
        Route::put('/profile', [ApiProfileController::class, 'update']);

        Route::get('/company', [ApiCompanyController::class, 'show']);
        Route::put('/company', [ApiCompanyController::class, 'update']);
        Route::delete('/company', [ApiCompanyController::class, 'destroy']);

        Route::get('/subscription', [ApiSubscriptionController::class, 'show']);
        Route::post('/subscription/checkout', [ApiSubscriptionController::class, 'checkout']);
        Route::post('/subscription/cancel', [ApiSubscriptionController::class, 'cancel']);

        Route::apiResource('vendor-categories', ApiVendorCategoryController::class);
        Route::apiResource('vendors', ApiVendorController::class);
    });
});

<?php

namespace App\Modules\Api\Http\Controllers\Webhooks;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Services\Payments\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends ApiController
{
    /**
     * Handle an incoming Stripe webhook event.
     *
     * Verifies the signature header before delegating to the payment service.
     * Must remain unauthenticated and exempt from CSRF middleware.
     */
    public function handle(Request $request, StripePaymentService $stripeService): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature') ?? '';

        $success = $stripeService->handleWebhook($payload, $sigHeader);

        if (! $success) {
            return $this->errorResponse('Invalid webhook signature or payload.', 400);
        }

        return $this->successResponse(null, 'Webhook received and processed successfully.');
    }
}

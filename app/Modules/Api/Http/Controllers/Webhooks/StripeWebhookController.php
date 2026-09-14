<?php

namespace App\Modules\Api\Http\Controllers\Webhooks;

use App\Services\Payments\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * متحكم استقبال وتأكيد إشعارات بوابة الدفع Stripe (Stripe Webhook Controller).
 *
 * يستقبل الإشعارات الآمنة الصادرة من خوادم Stripe في الخلفية ويفوض معالجتها للخدمة المركزية.
 */
class StripeWebhookController
{
    /**
     * معالجة إشعار الويب هوك والتحقق من التوقيع الرقمي.
     */
    public function handle(Request $request, StripePaymentService $stripeService): JsonResponse
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature') ?? '';

        $success = $stripeService->handleWebhook($payload, $sigHeader);

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature or payload.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook received and processed successfully.',
        ], 200);
    }
}

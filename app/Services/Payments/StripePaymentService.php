<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Tenant;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\PlanPrice;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentService
{
    public function __construct(protected SubscriptionService $subscriptionService)
    {
        $secretKey = config('stripe.secret_key') ?: env('STRIPE_SECRET');
        if ($secretKey) {
            Stripe::setApiKey($secretKey);
        }
    }

    /**
     * Create a pending payment record and generate a Stripe Checkout session URL.
     */
    public function createCheckoutSession(Tenant $tenant, Plan $plan, PlanPrice $planPrice, string $successUrl, string $cancelUrl)
    {
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'plan_price_id' => $planPrice->id,
            'amount' => $planPrice->price,
            'currency' => $planPrice->currency ?? 'SAR',
            'billing_cycle' => $planPrice->billing_cycle,
            'status' => 'pending',
            'gateway' => 'stripe',
        ]);

        $unitAmount = (int) round((float) $planPrice->price * 100);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'locale' => 'ar',
            'customer_email' => $tenant->email,
            'client_reference_id' => (string) $payment->id,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($planPrice->currency ?? 'sar'),
                        'unit_amount' => $unitAmount,
                        'product_data' => [
                            'name' => 'ترقية '.$plan->name.' ('.($planPrice->billing_cycle === 'yearly' ? 'اشتراك سنوي' : 'اشتراك شهري').')',
                            'description' => 'منشأة: '.$tenant->name,
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        $payment->update([
            'gateway_reference_id' => $session->id,
        ]);

        return $session->url;
    }

    /**
     * Verify Stripe webhook signature and process events.
     */
    public function handleWebhook(string $payload, string $sigHeader)
    {
        $secret = config('stripe.webhook_secret') ?: env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Signature Verification Failed: '.$e->getMessage());

            return false;
        }

        if ($event->type === 'checkout.session.completed') {
            /** @var Session $session */
            $session = $event->data->object;
            $this->processPaymentSuccess($session);
        }

        return true;
    }

    /**
     * Process successful payment and activate subscription plan.
     */
    protected function processPaymentSuccess(Session $session)
    {
        $paymentId = $session->metadata->payment_id ?? null;
        if (! $paymentId) {
            return;
        }

        DB::transaction(function () use ($paymentId, $session) {
            $payment = Payment::with(['tenant', 'plan'])->find($paymentId);

            if (! $payment || $payment->isPaid()) {
                return;
            }

            $payment->update([
                'status' => 'paid',
                'gateway_reference_id' => $session->payment_intent ?? $session->id,
                'payment_method' => $session->payment_method_types[0] ?? 'card',
                'receipt_url' => $this->getReceiptUrl($session->payment_intent),
                'paid_at' => now(),
            ]);

            $periodEnd = $payment->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth();
            $this->subscriptionService->activatePlan($payment->tenant, $payment->plan, $payment->billing_cycle, $periodEnd);
        });
    }

    /**
     * Retrieve receipt URL from Stripe charge.
     */
    protected function getReceiptUrl(?string $paymentIntentId): ?string
    {
        if (! $paymentIntentId) {
            return null;
        }

        return rescue(function () use ($paymentIntentId) {
            $intent = PaymentIntent::retrieve([
                'id' => $paymentIntentId,
                'expand' => ['latest_charge'],
            ]);

            return $intent->latest_charge->receipt_url ?? null;
        }, null, false);
    }
}

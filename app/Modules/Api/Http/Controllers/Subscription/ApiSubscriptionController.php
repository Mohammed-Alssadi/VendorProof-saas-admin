<?php

namespace App\Modules\Api\Http\Controllers\Subscription;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Subscription\CheckoutSubscriptionRequest;
use App\Modules\Web\Models\Plan;
use App\Services\Payments\StripePaymentService;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Manage tenant subscription lifecycle, tiers, and upgrades.
 */
class ApiSubscriptionController extends ApiController
{
    /**
     * Get active tenant subscription and plan details.
     *
     * Returns current plan limits, billing cycle, active subscription status,
     * renewal date, and locked feature flags for the tenant.
     */
    public function show(Request $request, SubscriptionService $subscriptionService)
    {
        $tenant = $request->user()?->tenant;

        if (! $tenant) {
            return $this->errorResponse('لم يتم العثور على منشأة مرتبطة بحسابك.', 404);
        }

        $data = $subscriptionService->getCurrentSubscriptionDetails($tenant);

        return $this->successResponse($data, 'تم استرجاع تفاصيل الاشتراك بنجاح.');
    }

    /**
     * Create a Stripe Checkout session for tenant plan upgrade.
     */
    public function checkout(CheckoutSubscriptionRequest $request, StripePaymentService $stripeService)
    {
        $tenant = $request->user()?->tenant;

        if (! $tenant || ! $tenant->isActive()) {
            return $this->errorResponse('حساب المنشأة غير نشط أو غير موجود.', 403);
        }

        $newPlan = Plan::active()->find($request->plan_id);

        if (! $newPlan) {
            return $this->errorResponse('الباقة المختارة غير متاحة حالياً.', 422);
        }

        if ($newPlan->id === $tenant->plan_id) {
            return $this->errorResponse('المنشأة مشتركة بالفعل في هذه الباقة.', 422);
        }

        // Prevent checkout for free plan
        if ($newPlan->isFree()) {
            return $this->errorResponse('الباقة المجانية لا تتطلب دفعاً مالياً.', 422);
        }

        // Locate real plan price record for requested cycle
        $billingCycle = $request->billing_cycle ?? 'monthly';
        $planPrice = $newPlan->activePriceFor($billingCycle);

        if (! $planPrice) {
            return $this->errorResponse('خيار الفوترة المحدد غير متاح لهذه الباقة.', 422, [
                'billing_cycle' => ['خيار الفوترة المحدد غير متاح لهذه الباقة.'],
            ]);
        }

        $defaultFrontendUrl = config('app.frontend_url') ?: 'http://localhost:3000';
        $successUrl = $request->success_url ?? $defaultFrontendUrl . '/billing?status=success';
        $cancelUrl  = $request->cancel_url ?? $defaultFrontendUrl . '/pricing?status=canceled';

        $checkoutUrl = $stripeService->createCheckoutSession(
            $tenant,
            $newPlan,
            $planPrice,
            $successUrl,
            $cancelUrl
        );

        return $this->successResponse([
            'checkout_url' => $checkoutUrl,
        ], 'تم إنشاء جلسة الدفع بنجاح.');
    }

    /**
     * Cancel active paid subscription.
     *
     * Cancels active paid subscription and cleanly rolls back tenant
     * to the default free tier without terminating the company account.
     */
    public function cancel(Request $request, SubscriptionService $subscriptionService)
    {
        $tenant = $request->user()?->tenant;

        if (! $tenant || ! $tenant->isActive()) {
            return $this->errorResponse('حساب المنشأة غير نشط أو غير موجود.', 403);
        }

        // Check if tenant is already on free tier or has no active paid subscription
        if ($tenant->plan?->isFree() || ! $tenant->currentSubscription) {
            return $this->errorResponse('أنت مشترك بالفعل في الباقة المجانية ولا يوجد اشتراك مدفوع لإلغائه. لإغلاق حساب المنشأة نهائياً، يرجى الانتقال إلى منطقة الخطر في إعدادات المنشأة.', 422);
        }

        $result = $subscriptionService->cancel($tenant);

        return $this->successResponse($result, 'تم إلغاء الاشتراك بنجاح وإعادة حسابك إلى الباقة المجانية.');
    }
}

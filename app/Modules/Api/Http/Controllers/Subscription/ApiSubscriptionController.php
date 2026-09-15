<?php

namespace App\Modules\Api\Http\Controllers\Subscription;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Subscription\CheckoutSubscriptionRequest;
use App\Modules\Web\Models\Plan;
use App\Services\Payments\StripePaymentService;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Http\Request;

class ApiSubscriptionController extends ApiController
{
    public function show(Request $request, SubscriptionService $subscriptionService)
    {
        $tenant = $request->user()?->tenant;

        if (! $tenant) {
            return $this->errorResponse('لم يتم العثور على منشأة مرتبطة بحسابك.', 404);
        }

        return $this->successResponse(
            $subscriptionService->getCurrentSubscriptionDetails($tenant),
            'تم استرجاع تفاصيل الاشتراك بنجاح.'
        );
    }

    /**
     * Create a Stripe Checkout session for a paid plan upgrade.
     *
     * Validates that the target plan is active, paid, and different from the current plan
     * before generating the hosted checkout URL.
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

        if ($newPlan->isFree()) {
            return $this->errorResponse('الباقة المجانية لا تتطلب دفعاً مالياً.', 422);
        }

        $billingCycle = $request->billing_cycle ?? 'monthly';
        $planPrice    = $newPlan->activePriceFor($billingCycle);

        if (! $planPrice) {
            return $this->errorResponse('خيار الفوترة المحدد غير متاح لهذه الباقة.', 422, [
                'billing_cycle' => ['خيار الفوترة المحدد غير متاح لهذه الباقة.'],
            ]);
        }

        $defaultFrontendUrl = config('app.frontend_url') ?: 'http://localhost:3000';

        $checkoutUrl = $stripeService->createCheckoutSession(
            $tenant,
            $newPlan,
            $planPrice,
            $request->success_url ?? $defaultFrontendUrl . '/billing?status=success',
            $request->cancel_url  ?? $defaultFrontendUrl . '/pricing?status=canceled'
        );

        return $this->successResponse(['checkout_url' => $checkoutUrl], 'تم إنشاء جلسة الدفع بنجاح.');
    }

    /**
     * Cancel the active paid subscription and revert the tenant to the free tier.
     */
    public function cancel(Request $request, SubscriptionService $subscriptionService)
    {
        $tenant = $request->user()?->tenant;

        if (! $tenant || ! $tenant->isActive()) {
            return $this->errorResponse('حساب المنشأة غير نشط أو غير موجود.', 403);
        }

        if ($tenant->plan?->isFree() || ! $tenant->currentSubscription) {
            return $this->errorResponse('أنت مشترك بالفعل في الباقة المجانية ولا يوجد اشتراك مدفوع لإلغائه. لإغلاق حساب المنشأة نهائياً، يرجى الانتقال إلى منطقة الخطر في إعدادات المنشأة.', 422);
        }

        return $this->successResponse(
            $subscriptionService->cancel($tenant),
            'تم إلغاء الاشتراك بنجاح وإعادة حسابك إلى الباقة المجانية.'
        );
    }
}

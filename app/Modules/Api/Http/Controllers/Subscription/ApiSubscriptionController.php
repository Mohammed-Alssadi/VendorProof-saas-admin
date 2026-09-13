<?php

namespace App\Modules\Api\Http\Controllers\Subscription;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Subscription\UpgradeSubscriptionRequest;
use App\Modules\Web\Models\Plan;
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
     * Upgrade tenant to a new subscription tier.
     *
     * Atomically switches tenant plan, updates active subscription billing
     * cycle and period dates, and unlocks new tier capabilities immediately.
     */
    public function upgrade(UpgradeSubscriptionRequest $request, SubscriptionService $subscriptionService)
    {
        $tenant = $request->user()?->tenant;

        if (! $tenant || ! $tenant->isActive()) {
            return $this->errorResponse('حساب المنشأة غير نشط أو غير موجود.', 403);
        }

        $newPlan = Plan::where('status', 'active')->find($request->plan_id);

        if (! $newPlan) {
            return $this->errorResponse('الباقة المختارة غير متاحة حالياً.', 422);
        }

        // Prevent redundant upgrade to the current plan
        if ((int) $tenant->plan_id === (int) $newPlan->id) {
            return $this->errorResponse('أنت مشترك بالفعل في هذه الباقة.', 422, [
                'plan_id' => ['أنت مشترك بالفعل في هذه الباقة.'],
            ]);
        }

        // Locate real plan price record for requested cycle
        $billingCycle = $request->billing_cycle ?? 'monthly';
        $planPrice = $newPlan->prices()
            ->where('billing_cycle', $billingCycle)
            ->where('status', 'active')
            ->first();

        if (! $planPrice) {
            return $this->errorResponse('خيار الفوترة المحدد غير متاح لهذه الباقة.', 422, [
                'billing_cycle' => ['خيار الفوترة المحدد غير متاح لهذه الباقة.'],
            ]);
        }

        $result = $subscriptionService->upgrade($tenant, $newPlan, $planPrice);

        return $this->successResponse($result, 'تمت ترقية باقة المنشأة بنجاح.');
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

<?php

namespace App\Services\Subscriptions;

use App\Models\Tenant;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Cancel active subscriptions, activate the given plan, and optionally set a custom expiry.
     */
    public function activatePlan(Tenant $tenant, Plan $plan, string $billingCycle = 'monthly', ?Carbon $expiresAt = null): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $billingCycle, $expiresAt) {
            $tenant->update(['plan_id' => $plan->id]);

            $tenant->subscriptions()->where('status', 'active')->update(['status' => 'canceled']);

            $subscription = $this->createSubscription($tenant, $plan, $billingCycle);

            if ($expiresAt) {
                $subscription->update(['current_period_end' => $expiresAt]);
            }

            $tenant->refresh();

            return $subscription;
        });
    }

    public function createSubscription(Tenant $tenant, Plan $plan, string $billingCycle = 'monthly'): Subscription
    {
        $planPrice = $plan->prices()
            ->where('billing_cycle', $billingCycle)
            ->where('status', 'active')
            ->first() ?? $plan->prices()->first();

        $currentPeriodEnd = null;

        if ($planPrice && (float) $planPrice->price > 0) {
            $currentPeriodEnd = $planPrice->billing_cycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth();
        }

        return Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_price_id' => $planPrice?->id,
            'status' => 'active',
            'starts_at' => now(),
            'current_period_end' => $currentPeriodEnd,
        ]);
    }

    public function getCurrentSubscriptionDetails(Tenant $tenant): array
    {
        $tenant->refresh();
        $tenant->load(['plan', 'currentSubscription.planPrice']);

        $subscription = $tenant->currentSubscription;
        $planPrice = $subscription?->planPrice;

        return [
            'tenant' => $tenant->toApiArray(),
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'billing_cycle' => $planPrice?->billing_cycle ?? 'monthly',
                'price' => $planPrice?->price ?? '0.00',
                'currency' => $planPrice?->currency ?? 'SAR',
                'currency_symbol' => $planPrice?->currency_symbol ?? 'ر.س',
                'starts_at' => $subscription->starts_at?->toISOString(),
                'current_period_end' => $subscription->current_period_end?->toISOString(),
            ] : null,
            'locked_features' => $tenant->lockedFeatureKeys(),
        ];
    }

    /**
     * Cancel the active paid subscription and revert the tenant to the free tier.
     *
     * Sets canceled_at in the returned payload so the frontend can display accurate feedback.
     */
    public function cancel(Tenant $tenant): array
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->subscriptions()->where('status', 'active')->update(['status' => 'canceled']);

            $freePlan = Plan::defaultFree();

            if ($freePlan) {
                $tenant->update(['plan_id' => $freePlan->id]);
                $this->createSubscription($tenant, $freePlan, 'monthly');
            }

            $tenant->refresh();
            $details = $this->getCurrentSubscriptionDetails($tenant);

            if (isset($details['subscription'])) {
                $details['subscription']['status'] = 'canceled';
                $details['subscription']['canceled_at'] = now()->toISOString();
            }

            return $details;
        });
    }
}

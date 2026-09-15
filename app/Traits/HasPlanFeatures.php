<?php

namespace App\Traits;

use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;

trait HasPlanFeatures
{
    public function currentPlan(): ?Plan
    {
        $plan = $this->plan ?? $this->currentSubscription?->planPrice?->plan;

        if ($plan && $plan->isPaid()) {
            $subscription = $this->currentSubscription;
            if (! $subscription || ! $subscription->isActive()) {
                return Plan::defaultFree();
            }
        }

        return $plan ?? Plan::defaultFree();
    }

    public function canUseFeature(string $featureKey): bool
    {
        $plan = $this->currentPlan();

        return $plan ? $plan->hasFeature($featureKey) : false;
    }

    public function getFeatureLimit(string $featureKey): int
    {
        $plan = $this->currentPlan();

        if (! $plan) {
            return 0;
        }

        if ($plan->isUnlimited($featureKey)) {
            return -1;
        }

        return (int) $plan->getLimit($featureKey);
    }

    /**
     * Check whether the tenant has consumed its plan limit for a given feature.
     *
     * Returns false when the limit is -1 (unlimited). Used before creating
     * resource records to enforce plan-based capacity boundaries.
     */
    public function hasReachedLimit(string $featureKey, int $currentCount): bool
    {
        $limit = $this->getFeatureLimit($featureKey);

        if ($limit === -1) {
            return false;
        }

        return $currentCount >= $limit;
    }

    public function lockedFeatureKeys(): array
    {
        $plan = $this->currentPlan();

        if (! $plan) {
            return [];
        }

        $planFeatures = $plan->features->keyBy('key');
        $allFeatures  = Feature::all();
        $locked       = [];

        foreach ($allFeatures as $feature) {
            $pf = $planFeatures->get($feature->key);

            if ($feature->type === 'feature' && (! $pf || $pf->pivot->value !== '1')) {
                $locked[] = $feature->key;
            }

            if ($feature->type === 'limit' && (! $pf || $pf->pivot->value === '0')) {
                $locked[] = $feature->key;
            }
        }

        return $locked;
    }
}

<?php

namespace App\Traits;

use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;

/**
 * سمة مخصصة لإدارة ميزات وحدود باقة المنشأة (Tenant Plan & Feature Gating).
 *
 * تفصل هذه السمة منطق التحقق من صلاحيات الباقة، استخراج الحدود، وحساب الميزات
 * المقفلة عن موديل المنشأة، ليبقى الموديل نظيفاً وخفيفاً.
 */
trait HasPlanFeatures
{
    /**
     * جلب الباقة الحالية المفعلة للمنشأة (مباشرة عبر plan أو الاشتراك).
     */
    public function currentPlan(): ?Plan
    {
        return $this->plan ?? $this->currentSubscription?->planPrice?->plan;
    }

    /**
     * فحص هل تملك المنشأة صلاحية استخدام ميزة معينة في باقتها الحالية.
     */
    public function canUseFeature(string $featureKey): bool
    {
        $plan = $this->currentPlan();

        return $plan ? $plan->hasFeature($featureKey) : false;
    }

    /**
     * جلب الحد التشغيلي لميزة معينة (مثلاً: حد الموردين، أو -1 لغير محدود، أو 0 لمقفل).
     */
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
     * فحص ديناميكي عام: هل وصلت المنشأة للحد الأقصى لأي ميزة بناءً على استهلاكها الحالي؟
     *
     * @param string $featureKey مفتاح الميزة (مثلاً: max_vendors أو max_users)
     * @param int $currentCount العدد الحالي المستخدم فعلياً في قاعدة البيانات
     */
    public function hasReachedLimit(string $featureKey, int $currentCount): bool
    {
        $limit = $this->getFeatureLimit($featureKey);

        // -1 تعني أن الميزة غير محدودة للباقة
        if ($limit === -1) {
            return false;
        }

        return $currentCount >= $limit;
    }

    /**
     * فحص ديناميكي إيجابي: هل ما زال بإمكان المنشأة استهلاك المزيد من هذه الميزة؟
     */
    public function canUseMore(string $featureKey, int $currentCount): bool
    {
        return ! $this->hasReachedLimit($featureKey, $currentCount);
    }

    /**
     * جلب مصفوفة خفيفة بمفاتيح الميزات والحدود المقفلة في باقة المنشأة لمساعدة الفرونت إند.
     *
     * @return array<string> مصفوفة نصوص بمفاتيح الميزات المقفلة
     */
    public function lockedFeatureKeys(): array
    {
        $plan = $this->currentPlan();

        if (! $plan) {
            return [];
        }

        $planFeatures = $plan->features->keyBy('key');
        $allFeatures = Feature::all();

        $locked = [];
        foreach ($allFeatures as $feature) {
            $pf = $planFeatures->get($feature->key);

            // ميزة تشغيلية مقفلة (قيمتها ليست 1)
            if ($feature->type === 'feature' && (! $pf || $pf->pivot->value !== '1')) {
                $locked[] = $feature->key;
            }

            // حد تشغيلي مقفل (قيمته 0 أو غير مضاف للباقة)
            if ($feature->type === 'limit' && (! $pf || $pf->pivot->value === '0')) {
                $locked[] = $feature->key;
            }
        }

        return $locked;
    }
}

<?php

namespace App\Services\Subscriptions;

use App\Models\Tenant;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\PlanPrice;
use App\Modules\Web\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * خدمة إدارة عقود واشتراكات المنشآت (Central Subscription Service).
 *
 * المصدر المركزي الوحيد المسؤول عن إنشاء وإدارة وتجديد اشتراكات المنشآت.
 */
class SubscriptionService
{
    /**
     * الدالة المركزية الوحيدة لإنشاء عقد اشتراك رسمي للمنشأة في قاعدة البيانات.
     */
    public function createSubscription(Tenant $tenant, Plan $plan, string $billingCycle = 'monthly'): Subscription
    {
        // 1. جلب السعر الفعلي المناسب للباقة
        $planPrice = $plan->prices()
            ->where('billing_cycle', $billingCycle)
            ->where('status', 'active')
            ->first() ?? $plan->prices()->first();

        // 2. احتساب تاريخ انتهاء الفترة الحالية
        $currentPeriodEnd = null;
        if ($planPrice && (float) $planPrice->price > 0) {
            $currentPeriodEnd = $planPrice->billing_cycle === 'yearly'
                ? now()->addYear()
                : now()->addMonth();
        }

        // 3. إنشاء سجل الاشتراك الرسمي في جدول subscriptions
        return Subscription::create([
            'tenant_id'          => $tenant->id,
            'plan_price_id'      => $planPrice?->id,
            'status'             => 'active',
            'starts_at'          => now(),
            'current_period_end' => $currentPeriodEnd,
        ]);
    }

    /**
     * استخراج وتنسيق بيانات الاشتراك الفعلي والباقة الحالية للمنشأة.
     */
    public function getCurrentSubscriptionDetails(Tenant $tenant): array
    {
        $tenant->loadMissing(['plan', 'currentSubscription.planPrice']);

        $subscription = $tenant->currentSubscription;
        $planPrice = $subscription?->planPrice;

        return [
            'tenant'          => $tenant->toApiArray(),
            'subscription'    => $subscription ? [
                'id'                 => $subscription->id,
                'status'             => $subscription->status,
                'billing_cycle'      => $planPrice?->billing_cycle ?? 'monthly',
                'price'              => $planPrice?->price ?? '0.00',
                'currency'           => $planPrice?->currency ?? 'SAR',
                'currency_symbol'    => $planPrice?->currency_symbol ?? 'ر.س',
                'starts_at'          => $subscription->starts_at?->toISOString(),
                'current_period_end' => $subscription->current_period_end?->toISOString(),
            ] : null,
            'locked_features' => $tenant->lockedFeatureKeys(),
        ];
    }



    /**
     * ترقية أو تغيير باقة المنشأة بشكل ذري آمن.
     */
    public function upgrade(Tenant $tenant, Plan $newPlan, PlanPrice $planPrice): array
    {
        return DB::transaction(function () use ($tenant, $newPlan, $planPrice) {
            // 1. تحديث معرف باقة المنشأة المباشر
            $tenant->update(['plan_id' => $newPlan->id]);

            // 2. إلغاء أي اشتراكات سابقة نشطة لتفادي الازدواجية
            $tenant->subscriptions()
                ->where('status', 'active')
                ->update(['status' => 'canceled']);

            // 3. إنشاء الاشتراك الجديد عبر الدالة المركزية
            $this->createSubscription($tenant, $newPlan, $planPrice->billing_cycle);

            // 4. إرجاع تفاصيل الاشتراك والباقة المحدثة بالهيكل المعياري الموحد
            $tenant->refresh();

            return $this->getCurrentSubscriptionDetails($tenant);
        });
    }

    /**
     * إلغاء الاشتراك المدفوع وإعادة المنشأة تلقائياً للباقة المجانية الافتراضية باشتراك حقيقي.
     */
    public function cancel(Tenant $tenant): array
    {
        return DB::transaction(function () use ($tenant) {
            // 1. تحويل كافة الاشتراكات النشطة إلى ملغية
            $tenant->subscriptions()
                ->where('status', 'active')
                ->update(['status' => 'canceled']);

            // 2. إعادة تعيين المنشأة للباقة المجانية الافتراضية
            $freePlan = Plan::defaultFree();
            if ($freePlan) {
                $tenant->update(['plan_id' => $freePlan->id]);

                // 3. إنشاء اشتراك مجاني حقيقي نشط دائم مدى الحياة عبر الدالة المركزية
                $this->createSubscription($tenant, $freePlan, 'monthly');
            }

            // 4. إرجاع تفاصيل الاشتراك والباقة المحدثة بالهيكل المعياري الموحد
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

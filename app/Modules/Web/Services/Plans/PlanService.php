<?php

namespace App\Modules\Web\Services\Plans;

use App\Modules\Web\Http\Requests\Plans\StorePlanRequest;
use App\Modules\Web\Http\Requests\Plans\UpdatePlanRequest;
use App\Modules\Web\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * سيرفز إدارة العمليات المعقدة لباقات الاشتراك.
 * يتولى إدارة المعاملات المالية المترابطة (إنشاء الباقة، أسعار الفوترة، ومزامنة مصفوفة الميزات والحدود).
 */
class PlanService
{
    /**
     * إنشاء باقة اشتراك جديدة بالكامل كوحدة واحدة داخل معاملة قاعدة بيانات.
     * تشمل: السجل الأساسي للخطة، سعري الفوترة (شهري وسنوي)، ومزامنة قيم الحدود والميزات.
     */
    public function createPlan(StorePlanRequest $request): Plan
    {
        return DB::transaction(function () use ($request) {
            // 1. إنشاء سجل الباقة الأساسي في جدول plans
            $plan = Plan::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            // 2. إنشاء سعري الفوترة (شهري وسنوي) بالعملة المحددة في جدول plan_prices
            $plan->prices()->create([
                'billing_cycle' => 'monthly',
                'price' => $request->monthly_price,
                'currency' => $request->currency,
                'status' => 'active',
            ]);

            $plan->prices()->create([
                'billing_cycle' => 'yearly',
                'price' => $request->yearly_price,
                'currency' => $request->currency,
                'status' => 'active',
            ]);

            // 3. مزامنة مصفوفة الميزات والحدود مباشرة
            $this->syncFeatures($plan, $request);

            return $plan;
        });
    }

    /**
     * تحديث بيانات الخطة وأسعارها وميزاتها بأمان داخل معاملة قاعدة بيانات.
     */
    public function updatePlan(Plan $plan, UpdatePlanRequest $request): Plan
    {
        return DB::transaction(function () use ($plan, $request) {
            // 1. تحديث بيانات الخطة الأساسية
            $plan->update([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            // 2. تحديث أو إنشاء أسعار الفوترة (شهري وسنوي) بالقيم المحدثة
            $plan->prices()->updateOrCreate(
                ['billing_cycle' => 'monthly'],
                [
                    'price' => $request->monthly_price,
                    'currency' => $request->currency,
                    'status' => 'active',
                ]
            );

            $plan->prices()->updateOrCreate(
                ['billing_cycle' => 'yearly'],
                [
                    'price' => $request->yearly_price,
                    'currency' => $request->currency,
                    'status' => 'active',
                ]
            );

            // 3. مزامنة مصفوفة الميزات والحدود مباشرة
            $this->syncFeatures($plan, $request);

            return $plan;
        });
    }

    /**
     * مزامنة ميزات وحدود الباقة بأمان مع جدول plan_features.
     * تحتفظ حصرياً بالميزات المشمولة فعلياً (قيمة أكبر من 0، أو مفعلة 1، أو غير محدود -1)،
     * وتستبعد الصفر والفارغ بحيث لا تظهر في كارت الباقة ولا تعيق حذف الميزة من النظام.
     */
    public function syncFeatures(Plan $plan, Request|array $request): void
    {
        $rawFeatures = is_array($request) ? ($request['features'] ?? $request) : $request->input('features', []);

        $cleanFeatures = [];
        foreach ($rawFeatures as $featureId => $data) {
            $value = is_array($data) ? ($data['value'] ?? null) : $data;

            // إذا كانت القيمة فارغة أو نل أو صفراً، نعتبرها غير مشمولة ولا نربطها بالباقة
            if ($value === null || trim((string) $value) === '' || (string) $value === '0') {
                continue;
            }

            $cleanFeatures[$featureId] = ['value' => (string) $value];
        }

        $plan->features()->sync($cleanFeatures);
    }
}

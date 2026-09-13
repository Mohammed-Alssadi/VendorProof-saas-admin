<?php

namespace App\Services\Tenants;

use App\Models\Tenant;
use App\Modules\Api\Models\User;
use App\Modules\Web\Models\Plan;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * سيرفز تأسيس المنشآت وإدارة حساباتها (Tenant Provisioning Service).
 *
 * مسؤول حصرياً عن تأسيس المنشأة، حساب المدير، وتفويض إنشاء الاشتراك لخدمة الاشتراكات.
 */
class TenantService
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * إنشاء منشأة جديدة مع حساب مديرها واشتراكها الرسمي بأمان تام.
     *
     * @param  array  $tenantData  بيانات المنشأة (name, slug, email, phone, company_cr, status)
     * @param  array  $userData  بيانات حساب المدير (name, email, password)
     * @param  int|null  $planId  معرف الباقة المباشر (إذا تُرك فارغاً تُسند باقة Free الافتراضية)
     * @param  string  $billingCycle  دورة الفوترة (monthly أو yearly)
     * @return array ['tenant' => $tenant, 'user' => $user]
     */
    public function createTenant(array $tenantData, array $userData, ?int $planId = null, string $billingCycle = 'monthly'): array
    {
        return DB::transaction(function () use ($tenantData, $userData, $planId, $billingCycle) {
            $effectivePlan = $planId ? Plan::find($planId) : Plan::defaultFree();

            // 1. إنشاء سجل المنشأة وربطه بالباقة فوراً
            $tenant = Tenant::create([
                'plan_id'    => $effectivePlan?->id,
                'name'       => $tenantData['name'],
                'slug'       => strtolower(trim($tenantData['slug'])),
                'company_cr' => $tenantData['company_cr'] ?? null,
                'email'      => $tenantData['email'],
                'phone'      => $tenantData['phone'] ?? null,
                'status'     => $tenantData['status'] ?? 'active',
            ]);

            // 2. إنشاء حساب مدير المنشأة وتشفير كلمة المرور
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $userData['name'],
                'email'     => $userData['email'],
                'password'  => Hash::make($userData['password']),
                'status'    => 'active',
            ]);

            // 3. تفويض إنشاء عقد الاشتراك الرسمي لخدمة الاشتراكات المركزية
            if ($effectivePlan) {
                $this->subscriptionService->createSubscription($tenant, $effectivePlan, $billingCycle);
            }

            return [
                'tenant' => $tenant,
                'user'   => $user,
            ];
        });
    }

    /**
     * تحديث بيانات المنشأة وباقة اشتراكها ودورة فوترتها بشكل ذري متزامن.
     *
     * @param  Tenant  $tenant  سجل المنشأة المراد تعديلها
     * @param  array  $data  البيانات الرسمية المراد تحديثها
     * @param  int|null  $planId  معرف الباقة الجديدة في حال التبديل
     * @param  string  $billingCycle  دورة الفوترة (monthly أو yearly)
     */
    public function updateTenant(Tenant $tenant, array $data, ?int $planId = null, string $billingCycle = 'monthly'): Tenant
    {
        return DB::transaction(function () use ($tenant, $data, $planId, $billingCycle) {
            // 1. تحديث بيانات المنشأة الرسمية
            $tenant->update([
                'plan_id'    => $planId ?? $tenant->plan_id,
                'name'       => $data['name'] ?? $tenant->name,
                'slug'       => isset($data['slug']) ? strtolower(trim($data['slug'])) : $tenant->slug,
                'company_cr' => $data['company_cr'] ?? $tenant->company_cr,
                'email'      => $data['email'] ?? $tenant->email,
                'phone'      => $data['phone'] ?? $tenant->phone,
                'status'     => $data['status'] ?? $tenant->status,
            ]);

            // 2. تحديث الاشتراك الرسمي للمنشأة بسطرين مباشرين
            if ($planId && $newPlan = Plan::find($planId)) {
                $tenant->subscriptions()->where('status', 'active')->update(['status' => 'canceled']);
                $this->subscriptionService->createSubscription($tenant, $newPlan, $billingCycle);
            }

            return $tenant->refresh();
        });
    }
}

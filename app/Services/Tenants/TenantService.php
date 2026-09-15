<?php

namespace App\Services\Tenants;

use App\Models\Tenant;
use App\Modules\Api\Models\User;
use App\Modules\Web\Models\Plan;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantService
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Atomically provision a tenant, admin user, and initial subscription in a single transaction.
     */
    public function createTenant(array $tenantData, array $userData, ?int $planId = null, string $billingCycle = 'monthly'): array
    {
        return DB::transaction(function () use ($tenantData, $userData, $planId, $billingCycle) {
            $effectivePlan = $planId ? Plan::find($planId) : Plan::defaultFree();

            $tenant = Tenant::create([
                'plan_id'    => $effectivePlan?->id,
                'name'       => $tenantData['name'],
                'slug'       => strtolower(trim($tenantData['slug'])),
                'company_cr' => $tenantData['company_cr'] ?? null,
                'email'      => $tenantData['email'],
                'phone'      => $tenantData['phone'] ?? null,
                'status'     => $tenantData['status'] ?? 'active',
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $userData['name'],
                'email'     => $userData['email'],
                'password'  => Hash::make($userData['password']),
                'status'    => 'active',
            ]);

            if ($effectivePlan) {
                $this->subscriptionService->createSubscription($tenant, $effectivePlan, $billingCycle);
            }

            return ['tenant' => $tenant, 'user' => $user];
        });
    }

    /**
     * Update tenant profile and optionally switch the active subscription plan.
     */
    public function updateTenant(Tenant $tenant, array $data, ?int $planId = null, string $billingCycle = 'monthly'): Tenant
    {
        return DB::transaction(function () use ($tenant, $data, $planId, $billingCycle) {
            $tenant->update([
                'plan_id'    => $planId ?? $tenant->plan_id,
                'name'       => $data['name'] ?? $tenant->name,
                'slug'       => isset($data['slug']) ? strtolower(trim($data['slug'])) : $tenant->slug,
                'company_cr' => $data['company_cr'] ?? $tenant->company_cr,
                'email'      => $data['email'] ?? $tenant->email,
                'phone'      => $data['phone'] ?? $tenant->phone,
                'status'     => $data['status'] ?? $tenant->status,
            ]);

            if ($planId && $newPlan = Plan::find($planId)) {
                $this->subscriptionService->activatePlan($tenant, $newPlan, $billingCycle);
            }

            return $tenant->refresh();
        });
    }
}

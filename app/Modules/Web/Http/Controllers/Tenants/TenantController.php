<?php

namespace App\Modules\Web\Http\Controllers\Tenants;

use App\Models\Tenant;
use App\Modules\Web\Http\Controllers\Controller;
use App\Modules\Web\Http\Requests\Tenants\StoreTenantRequest;
use App\Modules\Web\Http\Requests\Tenants\UpdateTenantRequest;
use App\Modules\Web\Models\Plan;
use App\Services\Tenants\TenantService;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with(['plan', 'users'])->latest()->paginate(15);

        return view('tenants.index', compact('tenants'));
    }

    public function create()
    {
        $plans = Plan::active()->with('prices')->get();

        return view('tenants.create', compact('plans'));
    }

    public function store(StoreTenantRequest $request, TenantService $tenantService)
    {
        $tenantData = [
            'name'       => $request->name,
            'slug'       => $request->slug,
            'company_cr' => $request->company_cr,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'status'     => 'active',
        ];

        $userData = [
            'name'     => $request->admin_name,
            'email'    => $request->admin_email,
            'password' => $request->admin_password,
        ];

        $tenantService->createTenant(
            $tenantData,
            $userData,
            $request->plan_id,
            $request->billing_cycle ?? 'monthly'
        );

        return redirect()->route('tenants.index')
            ->with('success', 'تم تسجيل المنشأة وتفعيل حساب المدير بنجاح.');
    }

    public function show(Tenant $tenant)
    {
        $tenant->loadMissing(['plan.features', 'currentSubscription.planPrice', 'users']);

        return view('tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        $tenant->loadMissing(['plan', 'currentSubscription.planPrice']);
        $plans               = Plan::active()->with('prices')->get();
        $currentBillingCycle = $tenant->currentSubscription?->planPrice?->billing_cycle ?? 'monthly';

        return view('tenants.edit', compact('tenant', 'plans', 'currentBillingCycle'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant, TenantService $tenantService)
    {
        $tenantService->updateTenant(
            $tenant,
            $request->validated(),
            (int) $request->plan_id,
            $request->billing_cycle ?? 'monthly'
        );

        return redirect()->route('tenants.show', $tenant)
            ->with('success', 'تم تحديث بيانات المنشأة وباقة الاستخدام بنجاح');
    }

    /**
     * Toggle the tenant operational status between active and suspended.
     */
    public function toggleStatus(Tenant $tenant)
    {
        $newStatus = $tenant->isActive() ? 'suspended' : 'active';
        $tenant->update(['status' => $newStatus]);

        $message = $newStatus === 'active'
            ? 'تم تنشيط حساب المنشأة بنجاح.'
            : 'تم تعليق حساب المنشأة بنجاح.';

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('tenants.index')
            ->with('success', 'تمت أرشفة سجل المنشأة بنجاح من النظام');
    }
}

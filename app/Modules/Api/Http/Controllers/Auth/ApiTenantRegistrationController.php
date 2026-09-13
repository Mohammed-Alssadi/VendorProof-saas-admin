<?php

namespace App\Modules\Api\Http\Controllers\Auth;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Auth\ApiRegisterTenantRequest;
use App\Modules\Web\Models\Plan;
use App\Services\Tenants\TenantService;

/**
 * Self-service tenant registration and onboarding.
 */
class ApiTenantRegistrationController extends ApiController
{
    /**
     * Register a new company and provision administrator account.
     *
     * Creates a new tenant entity, sets up the primary user account, activates
     * the default freemium tier, and returns a personal access token.
     */
    public function register(ApiRegisterTenantRequest $request, TenantService $tenantService)
    {
        // 1. جلب باقة الاستخدام المجاني الافتراضية
        $freePlan = Plan::defaultFree();

        if (! $freePlan) {
            return $this->errorResponse('تعذر إتمام التسجيل حالياً، لا توجد باقة اشتراك افتراضية مهيأة في النظام.', 500);
        }

        // 2. تجهيز بيانات المنشأة والمدير
        $tenantData = [
            'name'       => $request->company_name,
            'slug'       => $request->slug,
            'company_cr' => $request->company_cr,
            'email'      => $request->company_email ?? $request->email,
            'phone'      => $request->phone,
            'status'     => 'active',
        ];

        $userData = [
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
        ];

        // 3. تنفيذ المعاملة الذرية السريعة عبر السيرفز
        $result = $tenantService->createTenant(
            $tenantData,
            $userData,
            $freePlan->id
        );

        $user = $result['user'];
        $tenant = $result['tenant'];

        // 4. إصدار رمز التوثيق الرقمي الفوري (Sanctum Bearer Token)
        $token = $user->createToken('client-token')->plainTextToken;

        return $this->successResponse([
            'token'  => $token,
            'user'   => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'tenant' => $tenant->toApiArray(),
            'locked_features' => $tenant->lockedFeatureKeys(),
        ], 'تم إنشاء حساب المنشأة وتفعيل باقة الاستخدام المجانية بنجاح.', 201);
    }
}

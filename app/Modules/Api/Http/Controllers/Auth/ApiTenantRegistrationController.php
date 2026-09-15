<?php

namespace App\Modules\Api\Http\Controllers\Auth;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Auth\ApiRegisterTenantRequest;
use App\Modules\Web\Models\Plan;
use App\Services\Tenants\TenantService;

class ApiTenantRegistrationController extends ApiController
{
    /**
     * Register a new tenant, provision the admin account, and issue a bearer token.
     */
    public function register(ApiRegisterTenantRequest $request, TenantService $tenantService)
    {
        $freePlan = Plan::defaultFree();

        if (! $freePlan) {
            return $this->errorResponse('تعذر إتمام التسجيل حالياً، لا توجد باقة اشتراك افتراضية مهيأة في النظام.', 500);
        }

        $result = $tenantService->createTenant(
            [
                'name'       => $request->company_name,
                'slug'       => $request->slug,
                'company_cr' => $request->company_cr,
                'email'      => $request->company_email ?? $request->email,
                'phone'      => $request->phone,
                'status'     => 'active',
            ],
            [
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => $request->password,
            ],
            $freePlan->id
        );

        $user   = $result['user'];
        $tenant = $result['tenant'];
        $token  = $user->createToken('client-token')->plainTextToken;

        return $this->successResponse([
            'token'           => $token,
            'user'            => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'tenant'          => $tenant->toApiArray(),
            'locked_features' => $tenant->lockedFeatureKeys(),
        ], 'تم إنشاء حساب المنشأة وتفعيل باقة الاستخدام المجانية بنجاح.', 201);
    }
}

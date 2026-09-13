<?php

namespace App\Modules\Api\Http\Controllers\Auth;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Auth\ApiLoginRequest;
use App\Modules\Api\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Client authentication and session management.
 */
class ApiAuthController extends ApiController
{
    /**
     * Authenticate user and issue API bearer token.
     *
     * Validates credentials against active tenants, ensures the account is active,
     * and generates a personal access token for subsequent API requests.
     */
    public function login(ApiLoginRequest $request)
    {
        $user = User::with('tenant')->where('email', $request->email)->first();

        // 1. Validate credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse('بيانات الدخول غير صحيحة، يرجى التأكد من البريد وكلمة المرور.', 401);
        }

        // 2. Ensure account and tenant are active
        if (! $user->isActive() || ($user->tenant && ! $user->tenant->isActive())) {
            return $this->errorResponse('الحساب أو المنشأة غير مفعلة، يرجى التواصل مع إدارة النظام.', 403);
        }

        // 3. Issue token
        $token = $user->createToken('client-token')->plainTextToken;

        return $this->successResponse([
            'token'  => $token,
            'user'   => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'tenant' => $user->tenant?->toApiArray(),
            'locked_features' => $user->tenant ? $user->tenant->lockedFeatureKeys() : [],
        ], 'تم تسجيل الدخول بنجاح.');
    }

    /**
     * Get authenticated client session and plan entitlements.
     *
     * Returns the active user identity, company metadata, assigned subscription tier,
     * operational limits, and locked feature flags.
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('tenant.plan');
        $tenant = $user->tenant;

        return $this->successResponse([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'tenant' => $tenant?->toApiArray(),
            'locked_features' => $tenant ? $tenant->lockedFeatureKeys() : [],
        ]);
    }

    /**
     * Terminate active API session.
     *
     * Revokes and deletes the current Sanctum personal access token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'تم تسجيل الخروج بنجاح وإبطال جلسة الاتصال.');
    }
}

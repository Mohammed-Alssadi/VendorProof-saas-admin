<?php

namespace App\Modules\Api\Http\Controllers\Auth;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Auth\ApiLoginRequest;
use App\Modules\Api\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends ApiController
{
    /**
     * Authenticate user and issue a Sanctum bearer token.
     */
    public function login(ApiLoginRequest $request)
    {
        $user = User::with('tenant')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse('بيانات الدخول غير صحيحة، يرجى التأكد من البريد وكلمة المرور.', 401);
        }

        if (! $user->isActive() || ($user->tenant && ! $user->tenant->isActive())) {
            return $this->errorResponse('الحساب أو المنشأة غير مفعلة، يرجى التواصل مع إدارة النظام.', 403);
        }

        $token = $user->createToken('client-token')->plainTextToken;

        return $this->successResponse([
            'token'           => $token,
            'user'            => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'tenant'          => $user->tenant?->toApiArray(),
            'locked_features' => $user->tenant ? $user->tenant->lockedFeatureKeys() : [],
        ], 'تم تسجيل الدخول بنجاح.');
    }

    /**
     * Return the authenticated user session, tenant metadata, and plan entitlements.
     */
    public function me(Request $request)
    {
        $user   = $request->user()->load('tenant.plan');
        $tenant = $user->tenant;

        return $this->successResponse([
            'user'            => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'tenant'          => $tenant?->toApiArray(),
            'locked_features' => $tenant ? $tenant->lockedFeatureKeys() : [],
        ]);
    }

    /**
     * Revoke the current access token and terminate the session.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'تم تسجيل الخروج بنجاح وإبطال جلسة الاتصال.');
    }
}

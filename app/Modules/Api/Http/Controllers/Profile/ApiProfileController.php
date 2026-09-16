<?php

namespace App\Modules\Api\Http\Controllers\Profile;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Profile\UpdateUserProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Manage authenticated user identity and personal profile.
 */
class ApiProfileController extends ApiController
{
    /**
     * Get authenticated user profile.
     *
     * Returns the personal identity details of the active user.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->toISOString(),
        ]);
    }

    /**
     * Update user profile information and credentials.
     *
     * Updates personal contact information and optionally changes password
     * after verifying the current password.
     */
    public function update(UpdateUserProfileRequest $request)
    {
        $user = $request->user();

        // Check if password change is requested
        if ($request->filled('password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('كلمة المرور الحالية غير صحيحة.', 422, [
                    'current_password' => ['كلمة المرور الحالية التي أدخلتها غير متطابقة مع سجلاتنا.'],
                ]);
            }

            $user->password = Hash::make($request->password);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->toISOString(),
        ], 'تم تحديث الملف الشخصي بنجاح.');
    }
}

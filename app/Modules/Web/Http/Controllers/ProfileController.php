<?php

namespace App\Modules\Web\Http\Controllers;

use App\Modules\Web\Http\Requests\Profile\UpdateAdminPasswordRequest;
use App\Modules\Web\Http\Requests\Profile\UpdateAdminProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Controller for managing admin profile information and security credentials.
 */
class ProfileController extends Controller
{
    public function index()
    {
        $admin = Auth::user();

        return view('profile.index', compact('admin'));
    }

    public function update(UpdateAdminProfileRequest $request)
    {
        $admin = Auth::user();

        $admin->fill($request->validated());
        $admin->save();

        return redirect()->route('profile.index')
            ->with('success', 'تم تحديث بيانات الحساب الشخصي بنجاح');
    }

    public function updatePassword(UpdateAdminPasswordRequest $request)
    {
        $admin = Auth::user();

        $admin->password = Hash::make($request->password);
        $admin->save();

        return redirect()->route('profile.index')
            ->with('success', 'تم تحديث كلمة المرور بنجاح');
    }
}

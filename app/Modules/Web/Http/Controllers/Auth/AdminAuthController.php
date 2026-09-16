<?php

namespace App\Modules\Web\Http\Controllers\Auth;

use App\Modules\Web\Http\Controllers\Controller;
use App\Modules\Web\Http\Requests\Auth\AdminLoginRequest;
use App\Modules\Web\Http\Requests\Auth\AdminRegisterRequest;
use App\Modules\Web\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Controller for platform super admin authentication sessions.
 */
class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $admin = Auth::user();

            if (! $admin->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['email' => 'تم تعليق هذا الحساب الإداري. يرجى مراجعة إدارة المنصة.']);
            }

            $request->session()->regenerate();

            return redirect()->route('dashboard')
                ->with('success', 'مرحباً بك! تم تسجيل الدخول بنجاح.');
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.']);
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(AdminRegisterRequest $request)
    {
        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'تم إنشاء حساب المشرف بنجاح.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'تم تسجيل الخروج بنجاح.');
    }
}

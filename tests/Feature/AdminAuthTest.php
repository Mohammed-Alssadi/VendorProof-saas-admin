<?php

namespace Tests\Feature;

use App\Modules\Web\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * اختبارات مصادقة مديري المنصة (Admin Authentication Test).
 *
 * تغطي التحقق من نموذج تسجيل الدخول، وحماية المسارات بالوسيط القياسي،
 * ومطابقة كلمات المرور المشفرة، وتسجيل الخروج الطبيعي بدون قارد صريح.
 */
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * اختبار فتح صفحة تسجيل الدخول بنجاح.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('تسجيل الدخول');
    }

    /**
     * اختبار حماية لوحة التحكم ومنع الضيوف من الوصول إليها وإعادة توجيههم لصفحة الدخول.
     */
    public function test_guest_cannot_access_dashboard_and_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * اختبار نجاح تسجيل دخول المشرف بالبيانات الصحيحة وتوجيهه للوحة التحكم.
     */
    public function test_admin_can_authenticate_using_login_screen(): void
    {
        $admin = Admin::create([
            'name' => 'مدير المنصة',
            'email' => 'admin@platform.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@platform.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * اختبار رفض تسجيل الدخول عند كتابة كلمة مرور خاطئة.
     */
    public function test_admin_cannot_authenticate_with_invalid_password(): void
    {
        Admin::create([
            'name' => 'مدير المنصة',
            'email' => 'admin@platform.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@platform.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * اختبار تسجيل خروج المشرف وإلغاء الجلسة بنجاح.
     */
    public function test_authenticated_admin_can_logout(): void
    {
        $admin = Admin::create([
            'name' => 'مدير المنصة',
            'email' => 'admin@platform.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\User;
use App\Modules\Web\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSanctumLeakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * التحقق من أن مسارات الـ API ترفض جلسات السوبر أدمن فوراً بـ 401 ولا تسرب أي بيانات للأدمن.
     */
    public function test_admin_session_is_strictly_rejected_by_api(): void
    {
        $admin = Admin::create([
            'name' => 'سوبر أدمن المنصة',
            'email' => 'superadmin@platform.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'web')->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    /**
     * التحقق من أن مستخدمي المنشآت الحقيقيين برمز الوصول الرقمي Bearer Token يعملون بنجاح 100%.
     */
    public function test_api_user_with_bearer_token_authenticates_successfully(): void
    {
        $tenant = Tenant::create([
            'name' => 'شركة تجريبية',
            'slug' => 'test-co',
            'company_cr' => '1010123456',
            'email' => 'info@testco.sa',
            'phone' => '0501234567',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'مستخدم العميل الفعلي',
            'email' => 'client@testco.sa',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $token = $user->createToken('client-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile');

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'مستخدم العميل الفعلي');
        $response->assertJsonPath('data.email', 'client@testco.sa');
    }
}

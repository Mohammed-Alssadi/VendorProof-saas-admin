<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\User;
use App\Modules\Web\Models\Admin;
use App\Modules\Web\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_user_cannot_update_profile_with_pure_number_or_single_char(): void
    {
        $tenant = Tenant::create([
            'name'  => 'شركة الاختبار',
            'slug'  => 'test-company',
            'email' => 'company@test.com',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'مدير النظام',
            'email'     => 'admin@test.com',
            'password'  => bcrypt('password'),
        ]);

        Sanctum::actingAs($user);

        // محاولة إرسال الاسم كرقم 1
        $response1 = $this->putJson('/api/v1/profile', [
            'name'  => '1',
            'email' => 'admin@test.com',
        ]);

        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // محاولة إرسال الاسم كأرقام مجردة 12345
        $response2 = $this->putJson('/api/v1/profile', [
            'name'  => '12345',
            'email' => 'admin@test.com',
        ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // الاسم الحقيقي ينجح
        $response3 = $this->putJson('/api/v1/profile', [
            'name'  => 'أحمد محمد',
            'email' => 'admin@test.com',
        ]);

        $response3->assertStatus(200);
        $this->assertEquals('أحمد محمد', $user->fresh()->name);
    }

    public function test_api_company_rejects_invalid_cr_and_phone(): void
    {
        $tenant = Tenant::create([
            'name'       => 'شركة الاختبار',
            'slug'       => 'test-company',
            'email'      => 'company@test.com',
            'company_cr' => '1010112233',
            'phone'      => '0501234567',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'مدير النظام',
            'email'     => 'admin@test.com',
            'password'  => bcrypt('password'),
        ]);

        Sanctum::actingAs($user);

        // سجل تجاري غير رقمي
        $response1 = $this->putJson('/api/v1/company', [
            'name'       => 'شركة الاختبار المحدثة',
            'email'      => 'company@test.com',
            'company_cr' => 'invalid-cr',
            'phone'      => '0501234567',
        ]);

        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['company_cr']);

        // هاتف غير صالح
        $response2 = $this->putJson('/api/v1/company', [
            'name'       => 'شركة الاختبار المحدثة',
            'email'      => 'company@test.com',
            'company_cr' => '1010112233',
            'phone'      => 'phone-abc',
        ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_web_admin_rejects_pure_number_name_on_profile_update(): void
    {
        $admin = Admin::create([
            'name'     => 'مشرف المنصة',
            'email'    => 'superadmin@vendorproof.sa',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($admin)->put(route('profile.update'), [
            'name'  => '1',
            'email' => 'superadmin@vendorproof.sa',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals('مشرف المنصة', $admin->fresh()->name);
    }
}

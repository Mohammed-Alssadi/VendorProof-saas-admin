<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\User;
use App\Modules\Api\Models\VendorCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'شركة الاختبار الأولى',
            'slug' => 'test-company-1',
            'email' => 'company1@test.com',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'مدير النظام 1',
            'email' => 'admin1@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        $response = $this->getJson('/api/v1/vendor-categories');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_category(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'name' => 'خدمات تقنية وبرمجية',
            'description' => 'موردو البرمجيات والبنية التحتية',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/vendor-categories', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم إنشاء التصنيف بنجاح',
                'data' => [
                    'name' => 'خدمات تقنية وبرمجية',
                    'description' => 'موردو البرمجيات والبنية التحتية',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('vendor_categories', [
            'tenant_id' => $this->tenant->id,
            'name' => 'خدمات تقنية وبرمجية',
        ]);
    }

    public function test_category_name_must_be_unique_for_same_tenant(): void
    {
        Sanctum::actingAs($this->user);

        VendorCategory::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'مقاولات عامة',
        ]);

        $response = $this->postJson('/api/v1/vendor-categories', [
            'name' => 'مقاولات عامة',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_different_tenants_can_have_same_category_name(): void
    {
        // تصنيف للمنشأة الأولى
        VendorCategory::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'مقاولات عامة',
        ]);

        // منشأة ومستخدم ثانٍ
        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'مدير النظام 2',
            'email' => 'admin2@test.com',
            'password' => bcrypt('password'),
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson('/api/v1/vendor-categories', [
            'name' => 'مقاولات عامة',
        ]);

        $response->assertStatus(201);
    }

    public function test_tenant_can_only_see_its_own_categories(): void
    {
        VendorCategory::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'تصنيف خاص بالشركة الأولى',
        ]);

        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        $otherCategory = VendorCategory::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'تصنيف خاص بالشركة الثانية',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/vendor-categories');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('تصنيف خاص بالشركة الأولى', $names);
        $this->assertNotContains('تصنيف خاص بالشركة الثانية', $names);
    }

    public function test_user_can_update_category(): void
    {
        Sanctum::actingAs($this->user);

        $category = VendorCategory::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'اسم قديم',
        ]);

        $response = $this->putJson("/api/v1/vendor-categories/{$category->id}", [
            'name' => 'اسم محدث',
            'description' => 'وصف محدث',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'اسم محدث',
                    'description' => 'وصف محدث',
                ],
            ]);
    }

    public function test_user_can_delete_category(): void
    {
        Sanctum::actingAs($this->user);

        $category = VendorCategory::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'تصنيف للحذف',
        ]);

        $response = $this->deleteJson("/api/v1/vendor-categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم حذف التصنيف بنجاح',
            ]);

        $this->assertDatabaseMissing('vendor_categories', [
            'id' => $category->id,
        ]);
    }
}

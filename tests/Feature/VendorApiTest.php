<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\User;
use App\Modules\Api\Models\Vendor;
use App\Modules\Api\Models\VendorCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private VendorCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'شركة الاختبار المعتمدة',
            'slug' => 'test-company-proof',
            'email' => 'company@test.com',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'مدير المشتريات',
            'email' => 'procurement@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->category = VendorCategory::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'تقنية المعلومات',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_vendors(): void
    {
        $response = $this->getJson('/api/v1/vendors');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_vendor(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'name' => 'شركة السحاب الرقمي للمقاولات',
            'contact_name' => 'سعد الخالدي',
            'email' => 'saad@cloud-contracting.sa',
            'phone' => '+966501234567',
            'vendor_category_id' => $this->category->id,
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/vendors', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم إضافة المورد بنجاح',
                'data' => [
                    'name' => 'شركة السحاب الرقمي للمقاولات',
                    'contact_name' => 'سعد الخالدي',
                    'email' => 'saad@cloud-contracting.sa',
                    'phone' => '+966501234567',
                    'status' => 'active',
                    'category' => [
                        'id' => $this->category->id,
                        'name' => 'تقنية المعلومات',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('vendors', [
            'tenant_id' => $this->tenant->id,
            'name' => 'شركة السحاب الرقمي للمقاولات',
            'contact_name' => 'سعد الخالدي',
            'email' => 'saad@cloud-contracting.sa',
        ]);
    }

    public function test_vendor_validation_rules_enforce_real_names_and_formats(): void
    {
        Sanctum::actingAs($this->user);

        // محاولة إرسال أرقام مجردة أو بريد غير صالح
        $response = $this->postJson('/api/v1/vendors', [
            'name' => '12345',
            'contact_name' => '999',
            'email' => 'not-an-email',
            'phone' => 'invalid-phone-letters',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'contact_name', 'email', 'phone']);
    }

    public function test_vendor_email_must_be_unique_for_same_tenant(): void
    {
        Sanctum::actingAs($this->user);

        Vendor::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'المورد الأول',
            'contact_name' => 'مسؤول المورد',
            'email' => 'duplicate@vendor.com',
        ]);

        $response = $this->postJson('/api/v1/vendors', [
            'name' => 'المورد الثاني',
            'contact_name' => 'مسؤول آخر',
            'email' => 'duplicate@vendor.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_different_tenants_can_have_vendor_with_same_email(): void
    {
        // مورد للمنشأة الأولى
        Vendor::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'المورد الأول',
            'contact_name' => 'مسؤول المورد',
            'email' => 'shared@vendor.com',
        ]);

        // إنشاء منشأة ومستخدم ثانٍ
        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'مدير الشركة الثانية',
            'email' => 'admin2@test.com',
            'password' => bcrypt('password'),
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson('/api/v1/vendors', [
            'name' => 'نفس المورد للشركة الثانية',
            'contact_name' => 'مسؤول التواصل',
            'email' => 'shared@vendor.com',
        ]);

        $response->assertStatus(201);
    }

    public function test_tenant_can_only_see_its_own_vendors(): void
    {
        Vendor::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'مورد خاص بالمنشأة الأولى',
            'contact_name' => 'أحمد الأول',
            'email' => 'vendor1@company1.com',
        ]);

        $otherTenant = Tenant::create([
            'name' => 'المنشأة الثانية',
            'slug' => 'tenant-two',
            'email' => 'two@test.com',
        ]);

        Vendor::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'مورد خاص بالمنشأة الثانية',
            'contact_name' => 'خالد الثاني',
            'email' => 'vendor2@company2.com',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/vendors');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('مورد خاص بالمنشأة الأولى', $names);
        $this->assertNotContains('مورد خاص بالمنشأة الثانية', $names);
    }

    public function test_user_can_view_single_vendor(): void
    {
        Sanctum::actingAs($this->user);

        $vendor = Vendor::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'name' => 'شركة التوريدات الطبية',
            'contact_name' => 'د. فيصل العتيبي',
            'email' => 'faisal@med-supplies.sa',
            'phone' => '+966551122334',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/vendors/{$vendor->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $vendor->id,
                    'name' => 'شركة التوريدات الطبية',
                    'contact_name' => 'د. فيصل العتيبي',
                    'email' => 'faisal@med-supplies.sa',
                    'status' => 'active',
                    'category' => [
                        'id' => $this->category->id,
                        'name' => 'تقنية المعلومات',
                    ],
                ],
            ]);
    }

    public function test_user_cannot_view_or_manipulate_another_tenants_vendor(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'المنشأة الأجنبية',
            'slug' => 'other-tenant',
            'email' => 'other@test.com',
        ]);

        $foreignVendor = Vendor::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'مورد المنشأة الأجنبية',
            'contact_name' => 'محمد',
            'email' => 'foreign@vendor.com',
        ]);

        Sanctum::actingAs($this->user);

        // محاولة العرض
        $this->getJson("/api/v1/vendors/{$foreignVendor->id}")->assertStatus(404);

        // محاولة التعديل
        $this->putJson("/api/v1/vendors/{$foreignVendor->id}", [
            'name' => 'محاولة اختراق الاسم',
        ])->assertStatus(404);

        // محاولة الحذف
        $this->deleteJson("/api/v1/vendors/{$foreignVendor->id}")->assertStatus(404);
    }

    public function test_user_can_update_vendor(): void
    {
        Sanctum::actingAs($this->user);

        $vendor = Vendor::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'الاسم السابق للمورد',
            'contact_name' => 'المسؤول السابق',
            'email' => 'old@vendor.com',
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/v1/vendors/{$vendor->id}", [
            'name' => 'الاسم الجديد للمورد المحدث',
            'contact_name' => 'مسؤول جديد',
            'status' => 'active',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'الاسم الجديد للمورد المحدث',
                    'contact_name' => 'مسؤول جديد',
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'الاسم الجديد للمورد المحدث',
            'status' => 'active',
        ]);
    }

    public function test_user_can_delete_vendor(): void
    {
        Sanctum::actingAs($this->user);

        $vendor = Vendor::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'مورد للحذف النهائي',
            'contact_name' => 'مسؤول',
            'email' => 'delete@vendor.com',
        ]);

        $response = $this->deleteJson("/api/v1/vendors/{$vendor->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم حذف المورد بنجاح',
            ]);

        $this->assertDatabaseMissing('vendors', [
            'id' => $vendor->id,
        ]);
    }
}

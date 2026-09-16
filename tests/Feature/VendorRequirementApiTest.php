<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\DocumentType;
use App\Modules\Api\Models\User;
use App\Modules\Api\Models\Vendor;
use App\Modules\Api\Models\VendorCategory;
use App\Modules\Api\Models\VendorCategoryRequirement;
use App\Modules\Api\Models\VendorRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorRequirementApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private VendorCategory $category;

    private Vendor $vendor;

    private DocumentType $documentType;

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

        $this->category = VendorCategory::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'مقاولات عامة',
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'name' => 'شركة الأفق للمقاولات',
            'contact_name' => 'محمد العتيبي',
            'email' => 'info@alofoq.sa',
            'phone' => '0501234567',
            'status' => 'active',
        ]);

        $this->documentType = DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'السجل التجاري',
            'code' => 'CR_DOC',
            'requires_expiry' => true,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_vendor_requirements(): void
    {
        $response = $this->getJson("/api/v1/vendors/{$this->vendor->id}/requirements");

        $response->assertStatus(401);
    }

    public function test_vendor_creation_auto_provisions_requirements_from_category(): void
    {
        Sanctum::actingAs($this->user);

        // Define a category requirement first
        VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
            'notes' => 'شرط أساسي للتصنيف',
        ]);

        // Create a new vendor under this category
        $response = $this->postJson('/api/v1/vendors', [
            'vendor_category_id' => $this->category->id,
            'name' => 'شركة الرواد للإنشاء',
            'contact_name' => 'فهد المنصور',
            'email' => 'contact@alrowad.sa',
            'phone' => '0501112233',
        ]);

        $response->assertStatus(201);
        $newVendorId = $response->json('data.id');

        // Verify requirements were auto-provisioned
        $this->assertDatabaseHas('vendor_requirements', [
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $newVendorId,
            'document_type_id' => $this->documentType->id,
            'status' => 'missing',
            'is_mandatory' => 1,
        ]);
    }

    public function test_authenticated_user_can_list_vendor_requirements_with_summary_and_score(): void
    {
        Sanctum::actingAs($this->user);

        $docType2 = DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'شهادة ضريبية',
            'code' => 'VAT_DOC',
        ]);

        // One valid requirement and one missing
        VendorRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
            'status' => VendorRequirement::STATUS_VALID,
        ]);

        VendorRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $docType2->id,
            'is_mandatory' => true,
            'status' => VendorRequirement::STATUS_MISSING,
        ]);

        $response = $this->getJson("/api/v1/vendors/{$this->vendor->id}/requirements");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total' => 2,
                        'valid' => 1,
                        'missing' => 1,
                        'compliance_percentage' => 50,
                    ],
                ],
            ]);
    }

    public function test_authenticated_user_can_manually_add_custom_requirement_to_vendor(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
            'notes' => 'متطلب استثنائي خاص بهذا المشروع',
        ];

        $response = $this->postJson("/api/v1/vendors/{$this->vendor->id}/requirements", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تمت إضافة المتطلب للمورد بنجاح',
                'data' => [
                    'vendor_id' => $this->vendor->id,
                    'document_type_id' => $this->documentType->id,
                    'status' => 'missing',
                    'notes' => 'متطلب استثنائي خاص بهذا المشروع',
                    'document_type' => [
                        'id' => $this->documentType->id,
                        'code' => 'CR_DOC',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('vendor_requirements', [
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $this->documentType->id,
        ]);
    }

    public function test_cannot_attach_duplicate_requirement_to_same_vendor(): void
    {
        Sanctum::actingAs($this->user);

        VendorRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $this->documentType->id,
        ]);

        $response = $this->postJson("/api/v1/vendors/{$this->vendor->id}/requirements", [
            'document_type_id' => $this->documentType->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type_id']);
    }

    public function test_different_tenants_can_have_isolated_vendor_requirements(): void
    {
        VendorRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $this->documentType->id,
        ]);

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

        $otherVendor = Vendor::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'مورد المنشأة الأخرى',
            'contact_name' => 'سالم الدوسري',
            'email' => 'vendor@other.com',
            'phone' => '0509998877',
        ]);

        $otherDocType = DocumentType::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'السجل التجاري للأخرى',
            'code' => 'CR_DOC',
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/v1/vendors/{$otherVendor->id}/requirements", [
            'document_type_id' => $otherDocType->id,
        ]);

        $response->assertStatus(201);
    }

    public function test_cannot_attach_document_type_belonging_to_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        $otherDocType = DocumentType::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'وثيقة أخرى',
            'code' => 'OTHER_DOC',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/vendors/{$this->vendor->id}/requirements", [
            'document_type_id' => $otherDocType->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type_id']);
    }

    public function test_user_cannot_view_or_modify_another_tenants_vendor(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        $otherVendor = Vendor::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'مورد سري',
            'contact_name' => 'مسؤول سري',
            'email' => 'secret@vendor.com',
            'phone' => '0501110000',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/vendors/{$otherVendor->id}/requirements");
        $response->assertStatus(404);
    }

    public function test_user_can_update_vendor_requirement(): void
    {
        Sanctum::actingAs($this->user);

        $requirement = VendorRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
            'notes' => 'ملاحظة قديمة',
        ]);

        $response = $this->putJson("/api/v1/vendor-requirements/{$requirement->id}", [
            'is_mandatory' => false,
            'notes' => 'ملاحظة جديدة محدثة',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $requirement->id,
                    'is_mandatory' => false,
                    'notes' => 'ملاحظة جديدة محدثة',
                ],
            ]);

        $this->assertDatabaseHas('vendor_requirements', [
            'id' => $requirement->id,
            'is_mandatory' => 0,
            'notes' => 'ملاحظة جديدة محدثة',
        ]);
    }

    public function test_user_can_waive_vendor_requirement_with_mandatory_reason(): void
    {
        Sanctum::actingAs($this->user);

        $requirement = VendorRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
            'status' => VendorRequirement::STATUS_MISSING,
        ]);

        $response = $this->postJson("/api/v1/vendor-requirements/{$requirement->id}/waive", [
            'reason' => 'المورد جهة حكومية معفاة نظاماً من هذا المتطلب',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $requirement->id,
                    'status' => VendorRequirement::STATUS_WAIVED,
                    'waived_reason' => 'المورد جهة حكومية معفاة نظاماً من هذا المتطلب',
                ],
            ]);

        $this->assertDatabaseHas('vendor_requirements', [
            'id' => $requirement->id,
            'status' => 'waived',
            'waived_reason' => 'المورد جهة حكومية معفاة نظاماً من هذا المتطلب',
        ]);

        $this->assertNotNull(VendorRequirement::find($requirement->id)->waived_at);
    }

    public function test_user_can_unwaive_vendor_requirement(): void
    {
        Sanctum::actingAs($this->user);

        $requirement = VendorRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $this->documentType->id,
            'status' => VendorRequirement::STATUS_WAIVED,
            'waived_at' => now(),
            'waived_reason' => 'سبب استثناء قديم',
        ]);

        $response = $this->postJson("/api/v1/vendor-requirements/{$requirement->id}/unwaive");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $requirement->id,
                    'status' => VendorRequirement::STATUS_MISSING,
                    'waived_reason' => null,
                    'waived_at' => null,
                ],
            ]);

        $this->assertDatabaseHas('vendor_requirements', [
            'id' => $requirement->id,
            'status' => 'missing',
            'waived_reason' => null,
            'waived_at' => null,
        ]);
    }

    public function test_user_can_re_sync_vendor_requirements_with_category(): void
    {
        Sanctum::actingAs($this->user);

        $newDocType = DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'شهادة التأمينات',
            'code' => 'GOSI_DOC',
        ]);

        // Add a new requirement to category later
        VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $newDocType->id,
            'is_mandatory' => true,
        ]);

        $response = $this->postJson("/api/v1/vendors/{$this->vendor->id}/sync-requirements");

        $response->assertStatus(200);

        $this->assertDatabaseHas('vendor_requirements', [
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $newDocType->id,
        ]);
    }

    public function test_user_can_delete_vendor_requirement(): void
    {
        Sanctum::actingAs($this->user);

        $requirement = VendorRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $this->vendor->id,
            'document_type_id' => $this->documentType->id,
        ]);

        $response = $this->deleteJson("/api/v1/vendor-requirements/{$requirement->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم حذف المتطلب بنجاح',
            ]);

        $this->assertDatabaseMissing('vendor_requirements', [
            'id' => $requirement->id,
        ]);
    }
}

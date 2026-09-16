<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\DocumentType;
use App\Modules\Api\Models\User;
use App\Modules\Api\Models\VendorCategory;
use App\Modules\Api\Models\VendorCategoryRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorCategoryRequirementApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private VendorCategory $category;

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
            'description' => 'تصنيف مقاولي الإنشاءات',
            'is_active' => true,
        ]);

        $this->documentType = DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'السجل التجاري',
            'code' => 'CR_DOC',
            'requires_expiry' => true,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_category_requirements(): void
    {
        $response = $this->getJson("/api/v1/vendor-categories/{$this->category->id}/requirements");

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_category_requirements_with_document_types(): void
    {
        Sanctum::actingAs($this->user);

        VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
            'notes' => 'يجب أن يكون سارياً لمدة لا تقل عن 3 أشهر',
        ]);

        $response = $this->getJson("/api/v1/vendor-categories/{$this->category->id}/requirements");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'vendor_category_id' => $this->category->id,
                        'document_type_id' => $this->documentType->id,
                        'is_mandatory' => true,
                        'notes' => 'يجب أن يكون سارياً لمدة لا تقل عن 3 أشهر',
                        'document_type' => [
                            'id' => $this->documentType->id,
                            'name' => 'السجل التجاري',
                            'code' => 'CR_DOC',
                        ],
                    ],
                ],
            ]);
    }

    public function test_authenticated_user_can_attach_requirement_to_category(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
            'notes' => 'شهادة معتمدة من وزارة التجارة',
        ];

        $response = $this->postJson("/api/v1/vendor-categories/{$this->category->id}/requirements", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تمت إضافة متطلب التصنيف بنجاح',
                'data' => [
                    'vendor_category_id' => $this->category->id,
                    'document_type_id' => $this->documentType->id,
                    'is_mandatory' => true,
                    'notes' => 'شهادة معتمدة من وزارة التجارة',
                    'document_type' => [
                        'id' => $this->documentType->id,
                        'code' => 'CR_DOC',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('vendor_category_requirements', [
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => 1,
        ]);
    }

    public function test_cannot_attach_duplicate_document_type_to_same_category(): void
    {
        Sanctum::actingAs($this->user);

        VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
        ]);

        $response = $this->postJson("/api/v1/vendor-categories/{$this->category->id}/requirements", [
            'document_type_id' => $this->documentType->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type_id']);
    }

    public function test_different_tenants_can_attach_same_document_type_code_independently(): void
    {
        VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
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

        $otherCategory = VendorCategory::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'مقاولات عامة 2',
        ]);

        $otherDocType = DocumentType::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'السجل التجاري للثانية',
            'code' => 'CR_DOC',
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/v1/vendor-categories/{$otherCategory->id}/requirements", [
            'document_type_id' => $otherDocType->id,
            'is_mandatory' => true,
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
            'name' => 'وثيقة منشأة أخرى',
            'code' => 'OTHER_DOC',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/vendor-categories/{$this->category->id}/requirements", [
            'document_type_id' => $otherDocType->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type_id']);
    }

    public function test_user_cannot_view_or_attach_requirements_to_another_tenants_category(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        $otherCategory = VendorCategory::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'تصنيف يخص الشركة الثانية',
        ]);

        Sanctum::actingAs($this->user);

        // Cannot list
        $response = $this->getJson("/api/v1/vendor-categories/{$otherCategory->id}/requirements");
        $response->assertStatus(404);

        // Cannot attach
        $postResponse = $this->postJson("/api/v1/vendor-categories/{$otherCategory->id}/requirements", [
            'document_type_id' => $this->documentType->id,
        ]);
        $postResponse->assertStatus(404);
    }

    public function test_user_can_update_category_requirement(): void
    {
        Sanctum::actingAs($this->user);

        $requirement = VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
            'notes' => 'ملاحظات قديمة',
        ]);

        $response = $this->putJson("/api/v1/category-requirements/{$requirement->id}", [
            'is_mandatory' => false,
            'notes' => 'ملاحظات محدثة: أصبحت الوثيقة اختيارية',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $requirement->id,
                    'is_mandatory' => false,
                    'notes' => 'ملاحظات محدثة: أصبحت الوثيقة اختيارية',
                ],
            ]);

        $this->assertDatabaseHas('vendor_category_requirements', [
            'id' => $requirement->id,
            'is_mandatory' => 0,
            'notes' => 'ملاحظات محدثة: أصبحت الوثيقة اختيارية',
        ]);
    }

    public function test_user_can_update_category_requirement_document_type(): void
    {
        Sanctum::actingAs($this->user);

        $newDocType = DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'شهادة السعودة',
            'code' => 'SAUDIZATION',
        ]);

        $requirement = VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
        ]);

        $response = $this->putJson("/api/v1/category-requirements/{$requirement->id}", [
            'document_type_id' => $newDocType->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $requirement->id,
                    'document_type_id' => $newDocType->id,
                    'document_type' => [
                        'id' => $newDocType->id,
                        'code' => 'SAUDIZATION',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('vendor_category_requirements', [
            'id' => $requirement->id,
            'document_type_id' => $newDocType->id,
        ]);
    }

    public function test_cannot_update_category_requirement_to_already_assigned_document_type(): void
    {
        Sanctum::actingAs($this->user);

        $secondDocType = DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'شهادة التأمينات',
            'code' => 'GOSI',
        ]);

        // First requirement with documentType
        $requirement1 = VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
        ]);

        // Second requirement with secondDocType
        VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $secondDocType->id,
            'is_mandatory' => true,
        ]);

        // Try to update requirement1 to secondDocType (already assigned to this category)
        $response = $this->putJson("/api/v1/category-requirements/{$requirement1->id}", [
            'document_type_id' => $secondDocType->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type_id']);
    }

    public function test_user_cannot_update_another_tenants_requirement(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        $otherCategory = VendorCategory::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'تصنيف للأخرى',
        ]);

        $otherDocType = DocumentType::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'وثيقة للأخرى',
            'code' => 'OTHER_DOC',
        ]);

        $otherRequirement = VendorCategoryRequirement::create([
            'tenant_id' => $otherTenant->id,
            'vendor_category_id' => $otherCategory->id,
            'document_type_id' => $otherDocType->id,
            'is_mandatory' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/category-requirements/{$otherRequirement->id}", [
            'is_mandatory' => false,
        ]);

        $response->assertStatus(404);
    }

    public function test_user_can_delete_category_requirement(): void
    {
        Sanctum::actingAs($this->user);

        $requirement = VendorCategoryRequirement::create([
            'tenant_id' => $this->tenant->id,
            'vendor_category_id' => $this->category->id,
            'document_type_id' => $this->documentType->id,
            'is_mandatory' => true,
        ]);

        $response = $this->deleteJson("/api/v1/category-requirements/{$requirement->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم حذف متطلب التصنيف بنجاح',
            ]);

        $this->assertDatabaseMissing('vendor_category_requirements', [
            'id' => $requirement->id,
        ]);
    }

    public function test_user_cannot_delete_another_tenants_requirement(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        $otherCategory = VendorCategory::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'تصنيف للأخرى',
        ]);

        $otherDocType = DocumentType::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'وثيقة للأخرى',
            'code' => 'OTHER_DOC',
        ]);

        $otherRequirement = VendorCategoryRequirement::create([
            'tenant_id' => $otherTenant->id,
            'vendor_category_id' => $otherCategory->id,
            'document_type_id' => $otherDocType->id,
            'is_mandatory' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/category-requirements/{$otherRequirement->id}");

        $response->assertStatus(404);
    }
}

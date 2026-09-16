<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\DocumentType;
use App\Modules\Api\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentTypeApiTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_document_types(): void
    {
        $response = $this->getJson('/api/v1/document-types');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_document_type(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'name' => 'السجل التجاري',
            'code' => 'CR_DOC',
            'description' => 'شهادة السجل التجاري الصادرة من وزارة التجارة',
            'requires_expiry' => true,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/document-types', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم إنشاء نوع الوثيقة بنجاح',
                'data' => [
                    'name' => 'السجل التجاري',
                    'code' => 'CR_DOC',
                    'description' => 'شهادة السجل التجاري الصادرة من وزارة التجارة',
                    'requires_expiry' => true,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('document_types', [
            'tenant_id' => $this->tenant->id,
            'code' => 'CR_DOC',
        ]);
    }

    public function test_document_type_code_and_name_must_be_unique_for_same_tenant(): void
    {
        Sanctum::actingAs($this->user);

        DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'السجل التجاري',
            'code' => 'CR_DOC',
            'requires_expiry' => true,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/document-types', [
            'name' => 'السجل التجاري',
            'code' => 'CR_DOC',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code']);
    }

    public function test_different_tenants_can_have_same_document_type_code(): void
    {
        DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'شهادة ضريبة القيمة المضافة',
            'code' => 'VAT_CERT',
            'requires_expiry' => false,
            'is_active' => true,
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

        Sanctum::actingAs($otherUser);

        $response = $this->postJson('/api/v1/document-types', [
            'name' => 'شهادة ضريبة القيمة المضافة',
            'code' => 'VAT_CERT',
            'requires_expiry' => false,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
    }

    public function test_tenant_can_only_see_its_own_document_types(): void
    {
        DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'وثيقة خاصة بالشركة الأولى',
            'code' => 'DOC_1',
            'requires_expiry' => false,
            'is_active' => true,
        ]);

        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        DocumentType::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'وثيقة خاصة بالشركة الثانية',
            'code' => 'DOC_2',
            'requires_expiry' => false,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/document-types');

        $response->assertStatus(200);
        $codes = collect($response->json('data'))->pluck('code')->all();

        $this->assertContains('DOC_1', $codes);
        $this->assertNotContains('DOC_2', $codes);
    }

    public function test_user_can_filter_document_types_by_active_status(): void
    {
        Sanctum::actingAs($this->user);

        DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'وثيقة نشطة',
            'code' => 'DOC_ACTIVE',
            'requires_expiry' => true,
            'is_active' => true,
        ]);

        DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'وثيقة معطلة',
            'code' => 'DOC_INACTIVE',
            'requires_expiry' => true,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/document-types?is_active=1');
        $response->assertStatus(200);

        $codes = collect($response->json('data'))->pluck('code')->all();
        $this->assertContains('DOC_ACTIVE', $codes);
        $this->assertNotContains('DOC_INACTIVE', $codes);
    }

    public function test_user_can_retrieve_single_document_type(): void
    {
        Sanctum::actingAs($this->user);

        $docType = DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'شهادة السعودة',
            'code' => 'SAUDIZATION',
            'requires_expiry' => true,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/document-types/{$docType->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $docType->id,
                    'name' => 'شهادة السعودة',
                    'code' => 'SAUDIZATION',
                ],
            ]);
    }

    public function test_user_cannot_view_other_tenant_document_type(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'شركة الاختبار الثانية',
            'slug' => 'test-company-2',
            'email' => 'company2@test.com',
        ]);

        $otherDoc = DocumentType::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'وثيقة سرية للأخرى',
            'code' => 'SECRET_DOC',
            'requires_expiry' => true,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/document-types/{$otherDoc->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_update_document_type_and_toggle_status(): void
    {
        Sanctum::actingAs($this->user);

        $docType = DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'شهادة التأمينات الاجتماعية',
            'code' => 'GOSI',
            'requires_expiry' => true,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/v1/document-types/{$docType->id}", [
            'name' => 'شهادة التأمينات الاجتماعية المحدثة',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'شهادة التأمينات الاجتماعية المحدثة',
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('document_types', [
            'id' => $docType->id,
            'name' => 'شهادة التأمينات الاجتماعية المحدثة',
            'is_active' => 0,
        ]);
    }

    public function test_user_can_delete_document_type(): void
    {
        Sanctum::actingAs($this->user);

        $docType = DocumentType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'وثيقة قديمة ملغاة',
            'code' => 'OLD_DOC',
            'requires_expiry' => false,
            'is_active' => false,
        ]);

        $response = $this->deleteJson("/api/v1/document-types/{$docType->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم حذف نوع الوثيقة بنجاح',
            ]);

        $this->assertDatabaseMissing('document_types', [
            'id' => $docType->id,
        ]);
    }
}

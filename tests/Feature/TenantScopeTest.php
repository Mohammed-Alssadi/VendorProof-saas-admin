<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبارات عزل بيانات المنشآت المتعددة (Tenant Scope Multi-Tenancy Isolation Test).
 *
 * تغطي التحقق من فاعلية النطاق العام (Global Scope) في منع تسرب البيانات بين المنشآت،
 * والملء التلقائي لمعرف المنشأة (tenant_id) عند إنشاء السجلات الجديدة عبر الموديل.
 */
class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * اختبار عزل الاستعلامات التلقائي عبر Global Scope والملء التلقائي لـ tenant_id.
     */
    public function test_tenant_scope_filters_queries_and_auto_fills_tenant_id(): void
    {
        // 1. إنشاء شركتين
        $tenantA = Tenant::create([
            'name' => 'شركة الأمل',
            'slug' => 'hope-co',
            'email' => 'info@hope.sa',
        ]);

        $tenantB = Tenant::create([
            'name' => 'شركة المجد',
            'slug' => 'glory-co',
            'email' => 'info@glory.sa',
        ]);

        // 2. إنشاء مستخدم أساسي لكل شركة (مع تمرير tenant_id يدوياً)
        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'أحمد من شركة الأمل',
            'email' => 'ahmed@hope.sa',
            'password' => 'password123',
        ]);

        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'سعيد من شركة المجد',
            'email' => 'saeed@glory.sa',
            'password' => 'password123',
        ]);

        // 3. المحاكاة: تسجيل الدخول كمستخدم شركة الأمل (Tenant A)
        $this->actingAs($userA);

        // إنشاء مستخدم جديد دون كتابة tenant_id - يجب أن يُملأ تلقائياً!
        $newUserA = User::create([
            'name' => 'موظف جديد في شركة الأمل',
            'email' => 'new_emp@hope.sa',
            'password' => 'password123',
        ]);

        $this->assertEquals($tenantA->id, $newUserA->tenant_id);

        // التحقق من العزل التلقائي: مستخدم شركة الأمل يرى فقط موظفي شركته
        $usersForTenantA = User::all();
        $this->assertCount(2, $usersForTenantA);
        $this->assertTrue($usersForTenantA->contains($userA));
        $this->assertTrue($usersForTenantA->contains($newUserA));
        $this->assertFalse($usersForTenantA->contains($userB));

        // 4. المحاكاة: تسجيل الدخول كمستخدم شركة المجد (Tenant B)
        $this->actingAs($userB);

        $usersForTenantB = User::all();
        $this->assertCount(1, $usersForTenantB);
        $this->assertTrue($usersForTenantB->contains($userB));
        $this->assertFalse($usersForTenantB->contains($userA));

        // 5. التحقق من دالة تجاوز العزل للسوبر أدمن withoutTenancy()
        $allUsers = User::withoutTenancy()->get();
        $this->assertCount(3, $allUsers);
    }
}

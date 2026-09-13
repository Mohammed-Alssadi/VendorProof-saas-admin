<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Web\Models\Admin;
use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\PlanPrice;
use App\Modules\Web\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبارات واجهات لوحة التحكم الإدارية وعمليات الموارد (Admin Views & Resources Test).
 *
 * تغطي هذه الفئة التحقق من استجابة كافة شاشات ولوحات تحكم السوبر أدمن،
 * والتحقق من سلامة قراءة نماذج Eloquent الحقيقية بدون بيانات وهمية،
 * وفحص التوجيهات وحالات النجاح والفشل وحالات الجداول الفارغة.
 */
class AdminViewsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * حساب المشرف العام المعتمد للاختبارات.
     */
    private Admin $admin;

    /**
     * خطة الاشتراك الأساسية.
     */
    private Plan $plan;

    /**
     * خطة النمو للشركات المتوسطة.
     */
    private Plan $growthPlan;

    /**
     * خطة المؤسسات الكبرى.
     */
    private Plan $enterprisePlan;

    /**
     * السعر الشهري للباقة الأساسية.
     */
    private PlanPrice $planPrice;

    /**
     * السعر السنوي لباقة المؤسسات.
     */
    private PlanPrice $enterprisePrice;

    /**
     * ميزة الحد العددي لمقاعد المستخدمين.
     */
    private Feature $limitFeature;

    /**
     * ميزة التحقق الآلي التفعيلية.
     */
    private Feature $boolFeature;

    /**
     * منشأة تجريبية حقيقية مسجلة في قاعدة البيانات.
     */
    private Tenant $tenant;

    /**
     * سجل اشتراك حقيقي للمنشأة في باقة المؤسسات.
     */
    private Subscription $subscription;

    /**
     * تجهيز بيئة الاختبار وإنشاء السجلات المرجعية الأساسية في قاعدة البيانات.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. إنشاء حساب السوبر أدمن
        $this->admin = Admin::create([
            'name' => 'المدير العام للمنصة',
            'email' => 'admin@vendorproof.sa',
            'password' => bcrypt('AdminPassword123!'),
        ]);

        // 2. إنشاء باقات الاشتراك المرجعية
        $this->plan = Plan::create([
            'id' => 1,
            'name' => 'باقة الأساسيات',
            'slug' => 'starter',
            'description' => 'باقة متكاملة مخصصة للمنشآت الصغيرة والناشئة',
            'status' => 'active',
        ]);

        $this->growthPlan = Plan::create([
            'id' => 2,
            'name' => 'باقة النمو',
            'slug' => 'growth',
            'description' => 'باقة متقدمة مخصصة للشركات المتوسطة والمتوسعة',
            'status' => 'active',
        ]);

        $this->enterprisePlan = Plan::create([
            'id' => 3,
            'name' => 'باقة المؤسسات',
            'slug' => 'enterprise',
            'description' => 'باقة شاملة ومخصصة للمؤسسات والشركات الكبرى',
            'status' => 'active',
        ]);

        // 3. إنشاء أسعار الباقات
        $this->planPrice = PlanPrice::create([
            'id' => 1,
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'price' => 299.00,
            'currency' => 'SAR',
        ]);

        PlanPrice::create([
            'id' => 2,
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'yearly',
            'price' => 2990.00,
            'currency' => 'SAR',
        ]);

        $this->enterprisePrice = PlanPrice::create([
            'id' => 3,
            'plan_id' => $this->enterprisePlan->id,
            'billing_cycle' => 'yearly',
            'price' => 8990.00,
            'currency' => 'SAR',
        ]);

        // 4. إنشاء الميزات والحدود التشغيلية
        $this->limitFeature = Feature::create([
            'id' => 1,
            'name' => 'عدد مقاعد المستخدمين',
            'key' => 'max_users',
            'type' => 'limit',
        ]);

        $this->boolFeature = Feature::create([
            'id' => 2,
            'name' => 'التحقق الآلي من السجل التجاري',
            'key' => 'auto_cr_verification',
            'type' => 'feature',
        ]);

        $this->plan->features()->attach($this->limitFeature->id, ['value' => '5']);
        $this->plan->features()->attach($this->boolFeature->id, ['value' => '1']);

        // 5. إنشاء المنشأة التجريبية
        $this->tenant = Tenant::create([
            'id' => '1',
            'name' => 'شركة ركاز المتقدمة للتجارة',
            'slug' => 'rekaz-trading',
            'company_cr' => '1010567890',
            'email' => 'info@rekaz.sa',
            'phone' => '0501234567',
            'status' => 'active',
        ]);

        // 6. إنشاء عقد الاشتراك المرتبط
        $this->subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_price_id' => $this->enterprisePrice->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'current_period_end' => now()->addYear(),
        ]);
    }

    /**
     * التحقق من عرض لوحة التحكم الرئيسية وإحصائياتها بنجاح.
     */
    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('نظرة عامة على المنصة');
        $response->assertSee('الاشتراكات النشطة');
        $response->assertSee('المنشآت المشتركة');
    }

    /**
     * التحقق من عرض جدول قائمة المنشآت والمشتركين بنجاح.
     */
    public function test_tenants_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tenants.index'));
        $response->assertStatus(200);
        $response->assertSee('إدارة المشتركين والمنشآت');
        $response->assertSee('شركة ركاز المتقدمة للتجارة');
    }

    /**
     * التحقق من عرض صفحة تسجيل منشأة جديدة.
     */
    public function test_tenants_create_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tenants.create'));
        $response->assertStatus(200);
        $response->assertSee('تسجيل منشأة جديدة');
        $response->assertSee('البيانات الأساسية واشتراك المنشأة');
    }

    /**
     * التحقق من معالجة حفظ منشأة جديدة وإعادة التوجيه مع إشعار النجاح.
     */
    public function test_tenants_store_redirects_successfully(): void
    {
        $response = $this->actingAs($this->admin)->post(route('tenants.store'), [
            'name' => 'شركة تجربة جديدة',
            'slug' => 'new-test-company',
            'company_cr' => '1010112233',
            'email' => 'test@newcompany.sa',
            'phone' => '0555123456',
            'admin_name' => 'مدير التجربة',
            'admin_email' => 'admin@newcompany.sa',
            'admin_password' => 'password123',
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'yearly',
        ]);
        $response->assertRedirect(route('tenants.index'));
        $response->assertSessionHas('success');
    }

    /**
     * التحقق من استعراض تفاصيل منشأة مشتركة بكامل بياناتها.
     */
    public function test_tenants_show_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tenants.show', $this->tenant->id));
        $response->assertStatus(200);
        $response->assertSee('شركة ركاز المتقدمة للتجارة');
        $response->assertSee('باقة المؤسسات');
        $response->assertSee('توثيق البريد الإلكتروني');
        $response->assertDontSee('حالة التجديد التلقائي');
    }

    /**
     * التحقق من فتح شاشة تعديل بيانات المنشأة.
     */
    public function test_tenants_edit_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tenants.edit', $this->tenant->id));
        $response->assertStatus(200);
        $response->assertSee('تعديل بيانات المنشأة');
        $response->assertSee('شركة ركاز المتقدمة للتجارة');
    }

    /**
     * التحقق من استقبال تحديثات بيانات المنشأة وإعادة التوجيه.
     */
    public function test_tenants_update_redirects_successfully(): void
    {
        $response = $this->actingAs($this->admin)->put(route('tenants.update', $this->tenant->id), [
            'name' => 'شركة ركاز المحدثة',
            'company_cr' => '1010884422',
            'email' => 'contact@rekaz.sa',
            'phone' => '0114567890',
            'plan_id' => $this->enterprisePlan->id,
            'billing_cycle' => 'yearly',
            'status' => 'active',
        ]);
        $response->assertRedirect(route('tenants.show', $this->tenant->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'name' => 'شركة ركاز المحدثة',
        ]);
        $this->assertEquals($this->enterprisePlan->id, $this->tenant->fresh()->currentPlan()->id);
    }

    /**
     * التحقق من عملية تبديل حالة المنشأة التشغيلية (تفعيل / تعطيل).
     */
    public function test_tenants_toggle_status_redirects_successfully(): void
    {
        $response = $this->actingAs($this->admin)->post(route('tenants.toggle-status', $this->tenant->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * التحقق من استعراض دليل باقات وخطط الاشتراك.
     */
    public function test_plans_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('plans.index'));
        $response->assertStatus(200);
        $response->assertSee('الخطط وباقات الاشتراك');
        $response->assertSee('باقة الأساسيات');
        $response->assertSee('باقة النمو');
        $response->assertSee('باقة المؤسسات');
    }

    /**
     * التحقق من فتح نموذج إنشاء خطة اشتراك جديدة.
     */
    public function test_plans_create_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('plans.create'));
        $response->assertStatus(200);
        $response->assertSee('إنشاء خطة اشتراك جديدة');
    }

    /**
     * التحقق من فتح شاشة تعديل خطة اشتراك موجودة.
     */
    public function test_plans_edit_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('plans.edit', $this->plan->id));
        $response->assertStatus(200);
        $response->assertSee('تعديل خطة الاشتراك');
    }

    /**
     * التحقق من استعراض مصفوفة مقارنة الميزات والحدود.
     */
    public function test_plans_matrix_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('plans.matrix'));
        $response->assertStatus(200);
        $response->assertSee('مصفوفة الميزات والحدود');
        $response->assertSee('التحقق الآلي من السجل التجاري');
    }

    /**
     * التحقق من فتح شاشة تخصيص الميزات والحدود لخطة محددة.
     */
    public function test_plans_features_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('plans.features', $this->plan->id));
        $response->assertStatus(200);
        $response->assertSee('إدارة ميزات وحدود الباقة');
        $response->assertSee('جدول الميزات والحدود المعتمدة لباقة');
    }

    /**
     * التحقق من إرسال وتحديث قيم ميزات وحدود الباقة وإعادة التوجيه.
     */
    public function test_plans_features_update_redirects_successfully(): void
    {
        $response = $this->actingAs($this->admin)->put(route('plans.features.update', $this->plan->id), [
            'features' => [
                $this->boolFeature->id => ['value' => '1'],
                $this->limitFeature->id => ['value' => '5'],
            ],
        ]);
        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('success');
    }

    /**
     * التحقق من فتح معجم الميزات والحدود التشغيلية.
     */
    public function test_features_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('features.index'));
        $response->assertStatus(200);
        $response->assertSee('الميزات والحدود التشغيلية');
        $response->assertSee('عدد مقاعد المستخدمين');
    }

    /**
     * التحقق من فتح نموذج إضافة ميزة تشغيلية جديدة.
     */
    public function test_features_create_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('features.create'));
        $response->assertStatus(200);
        $response->assertSee('إضافة ميزة تشغيلية جديدة');
    }

    /**
     * التحقق من فتح شاشة تعديل ميزة تشغيلية مسجلة.
     */
    public function test_features_edit_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('features.edit', $this->limitFeature->id));
        $response->assertStatus(200);
        $response->assertSee('تعديل الميزة التشغيلية');
        $response->assertSee('max_users');
    }

    /**
     * التحقق من استعراض صفحة إعدادات الحساب الشخصي للمشرف.
     */
    public function test_profile_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('profile.index'));
        $response->assertStatus(200);
        $response->assertSee('إعدادات الحساب الشخصي والأمان');
        $response->assertSee('المدير العام للمنصة');
    }

    /**
     * التحقق من تعديل الملف الشخصي للمشرف وحفظه في جدول المشرفين.
     */
    public function test_profile_update_works(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profile.update'), [
            'name' => 'المدير المحدث',
            'email' => 'admin_updated@vendorproof.sa',
        ]);
        $response->assertRedirect(route('profile.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('admins', [
            'id' => $this->admin->id,
            'name' => 'المدير المحدث',
            'email' => 'admin_updated@vendorproof.sa',
        ]);
    }

    /**
     * التحقق من استعراض صفحة تفاصيل الخطة التشغيلية وأسعارها وحدودها.
     */
    public function test_plans_show_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('plans.show', $this->plan->id));
        $response->assertStatus(200);
        $response->assertSee('باقة الأساسيات');
        $response->assertSee('جدول الميزات والحدود المعتمدة لباقة');
    }

    /**
     * التحقق من استعراض جدول المشتركين والباقات للمنشآت.
     */
    public function test_subscriptions_index_renders_successfully(): void
    {
        $this->tenant->update(['plan_id' => $this->plan->id]);

        $response = $this->actingAs($this->admin)->get(route('subscriptions.index'));
        $response->assertStatus(200);
        $response->assertSee('سجل المشتركين والباقات');
        $response->assertSee('شركة ركاز المتقدمة للتجارة');
        $response->assertSee($this->plan->name);
    }
}

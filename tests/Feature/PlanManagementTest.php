<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Web\Models\Admin;
use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test for SaaS plans, pricing tiers, and feature matrix management.
 */
class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'مدير النظام',
            'email' => 'admin@vendorproof.sa',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_guest_cannot_access_plan_management(): void
    {
        $this->get(route('plans.index'))->assertRedirect(route('login'));
        $this->get(route('plans.create'))->assertRedirect(route('login'));
        $this->post(route('plans.store'), [])->assertRedirect(route('login'));
        $this->get(route('plans.matrix'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_plans_index(): void
    {
        $plan = Plan::create([
            'name' => 'باقة النمو',
            'slug' => 'growth',
            'description' => 'وصف باقة النمو',
            'status' => 'active',
        ]);

        $plan->prices()->create([
            'billing_cycle' => 'monthly',
            'price' => 299,
            'currency' => 'SAR',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('plans.index'));

        $response->assertStatus(200);
        $response->assertSee('باقة النمو');
        $response->assertSee('299');
    }

    public function test_admin_can_view_create_plan_page(): void
    {
        Feature::create(['name' => 'عدد الموردين', 'key' => 'max_vendors', 'type' => 'limit']);
        Feature::create(['name' => 'الامتثال السحابي', 'key' => 'cloud_compliance', 'type' => 'feature']);

        $response = $this->actingAs($this->admin)->get(route('plans.create'));

        $response->assertStatus(200);
        $response->assertSee('عدد الموردين');
        $response->assertSee('الامتثال السحابي');
    }

    public function test_admin_can_store_plan_with_prices_and_features(): void
    {
        $limit = Feature::create(['name' => 'حد الموردين', 'key' => 'max_vendors', 'type' => 'limit']);
        $feature = Feature::create(['name' => 'الذكاء الاصطناعي', 'key' => 'ai_audit', 'type' => 'feature']);

        $payload = [
            'name' => 'باقة المؤسسات المتقدمة',
            'slug' => 'enterprise-advanced',
            'monthly_price' => 499.00,
            'yearly_price' => 4990.00,
            'currency' => 'SAR',
            'status' => 'active',
            'description' => 'باقة شاملة لفحص واعتماد الموردين بسلاسة واحترافية',
            'features' => [
                $limit->id => ['value' => '250'],
                $feature->id => ['value' => '1'],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('plans.store'), $payload);

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('plans', [
            'name' => 'باقة المؤسسات المتقدمة',
            'slug' => 'enterprise-advanced',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('plan_prices', [
            'billing_cycle' => 'monthly',
            'price' => 499.00,
            'currency' => 'SAR',
        ]);

        $this->assertDatabaseHas('plan_prices', [
            'billing_cycle' => 'yearly',
            'price' => 4990.00,
            'currency' => 'SAR',
        ]);

        $this->assertDatabaseHas('plan_features', [
            'value' => '250',
        ]);

        $this->assertDatabaseHas('plan_features', [
            'value' => '1',
        ]);
    }

    public function test_admin_can_store_plan_with_unlimited_limit(): void
    {
        $feature = Feature::create(['name' => 'فحص السجلات', 'key' => 'cr_checks', 'type' => 'limit']);

        $payload = [
            'name' => 'باقة غير محدودة',
            'slug' => 'unlimited-plan',
            'monthly_price' => 999.00,
            'yearly_price' => 9990.00,
            'currency' => 'SAR',
            'status' => 'active',
            'description' => 'باقة للمؤسسات الضخمة بدون قيود عددية',
            'features' => [
                $feature->id => ['value' => '-1'],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('plans.store'), $payload);

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('plan_features', [
            'value' => '-1',
        ]);
    }

    public function test_plan_validation_rules_enforced(): void
    {
        $response = $this->actingAs($this->admin)->post(route('plans.store'), [
            'name' => '',
            'slug' => 'INVALID SLUG!@#',
            'monthly_price' => 'not-a-number',
            'yearly_price' => -10,
            'currency' => 'INVALID',
            'status' => 'unknown',
            'description' => str_repeat('a', 1005),
        ]);

        $response->assertSessionHasErrors([
            'name',
            'slug',
            'monthly_price',
            'yearly_price',
            'currency',
            'status',
            'description',
        ]);
    }

    public function test_plan_slug_must_be_unique(): void
    {
        Plan::create([
            'name' => 'باقة البداية',
            'slug' => 'starter',
            'description' => 'وصف الباقة',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post(route('plans.store'), [
            'name' => 'باقة أخرى',
            'slug' => 'starter',
            'monthly_price' => 99,
            'yearly_price' => 990,
            'currency' => 'SAR',
            'status' => 'active',
            'description' => 'وصف جديد',
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_admin_can_view_plan_show_page(): void
    {
        $plan = Plan::create([
            'name' => 'باقة تفصيلية',
            'slug' => 'detail-plan',
            'description' => 'تفاصيل الخطة',
            'status' => 'active',
        ]);

        $plan->prices()->create([
            'billing_cycle' => 'monthly',
            'price' => 150,
            'currency' => 'SAR',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('plans.show', $plan));

        $response->assertStatus(200);
        $response->assertSee('باقة تفصيلية');
        $response->assertSee('150');
    }

    public function test_admin_can_update_plan_details_and_prices(): void
    {
        $plan = Plan::create([
            'name' => 'الاسم القديم',
            'slug' => 'old-slug',
            'description' => 'الوصف القديم',
            'status' => 'active',
        ]);

        $plan->prices()->create([
            'billing_cycle' => 'monthly',
            'price' => 100,
            'currency' => 'SAR',
            'status' => 'active',
        ]);

        $plan->prices()->create([
            'billing_cycle' => 'yearly',
            'price' => 1000,
            'currency' => 'SAR',
            'status' => 'active',
        ]);

        $updatePayload = [
            'name' => 'الاسم الجديد المحدث',
            'slug' => 'updated-slug',
            'monthly_price' => 120.00,
            'yearly_price' => 1200.00,
            'currency' => 'USD',
            'status' => 'inactive',
            'description' => 'الوصف المحدث بالكامل',
        ];

        $response = $this->actingAs($this->admin)->put(route('plans.update', $plan), $updatePayload);

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'الاسم الجديد المحدث',
            'slug' => 'updated-slug',
            'status' => 'inactive',
            'description' => 'الوصف المحدث بالكامل',
        ]);

        $this->assertDatabaseHas('plan_prices', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 120.00,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('plan_prices', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'yearly',
            'price' => 1200.00,
            'currency' => 'USD',
        ]);
    }

    public function test_admin_can_update_features_from_dedicated_screen(): void
    {
        $limit = Feature::create(['name' => 'مساحة التخزين', 'key' => 'storage_gb', 'type' => 'limit']);
        $feature = Feature::create(['name' => 'دعم على مدار الساعة', 'key' => 'support_24_7', 'type' => 'feature']);

        $plan = Plan::create([
            'name' => 'باقة الدعم',
            'slug' => 'support-plan',
            'description' => 'وصف',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->put(route('plans.features.update', $plan), [
            'features' => [
                $limit->id => ['value' => '50'],
                $feature->id => ['value' => '1'],
            ],
        ]);

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('plan_features', [
            'plan_id' => $plan->id,
            'feature_id' => $limit->id,
            'value' => '50',
        ]);

        $this->assertDatabaseHas('plan_features', [
            'plan_id' => $plan->id,
            'feature_id' => $feature->id,
            'value' => '1',
        ]);
    }

    public function test_admin_can_view_plans_comparison_matrix(): void
    {
        Plan::create([
            'name' => 'باقة أ',
            'slug' => 'plan-a',
            'status' => 'active',
        ]);

        Feature::create([
            'name' => 'ميزة المقارنة',
            'key' => 'matrix_feat',
            'type' => 'feature',
        ]);

        $response = $this->actingAs($this->admin)->get(route('plans.matrix'));

        $response->assertStatus(200);
        $response->assertSee('باقة أ');
        $response->assertSee('ميزة المقارنة');
    }

    public function test_admin_can_delete_unattached_plan(): void
    {
        $plan = Plan::create([
            'name' => 'باقة للحذف',
            'slug' => 'to-delete-plan',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('plans.destroy', $plan));

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_admin_cannot_delete_plan_with_active_subscriptions(): void
    {
        $plan = Plan::create([
            'name' => 'باقة بها مشتركون',
            'slug' => 'subscribed-plan',
            'status' => 'active',
        ]);

        $price = $plan->prices()->create([
            'billing_cycle' => 'monthly',
            'price' => 199,
            'currency' => 'SAR',
            'status' => 'active',
        ]);

        $tenant = Tenant::create([
            'name' => 'شركة التقنية المتقدمة',
            'slug' => 'tech-corp',
            'email' => 'info@techcorp.sa',
            'status' => 'active',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_price_id' => $price->id,
            'status' => 'active',
            'starts_at' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('plans.destroy', $plan));

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('error');

        $this->assertNotSoftDeleted('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_plan_displays_dollar_symbol_when_currency_is_usd(): void
    {
        $plan = Plan::create([
            'name' => 'باقة الدولار العالمية',
            'slug' => 'global-usd-plan',
            'description' => 'باقة مسعرة بالدولار الأمريكي',
            'status' => 'active',
        ]);

        $plan->prices()->create([
            'billing_cycle' => 'monthly',
            'price' => 150,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $plan->prices()->create([
            'billing_cycle' => 'yearly',
            'price' => 1500,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        // التحقق من الدوال في الموديل
        $this->assertEquals('$', $plan->currency_symbol);
        $this->assertEquals('150.00 $', $plan->monthlyPrice->formattedPrice());

        // التحقق من الظهور في كروت الباقات والجدول
        $response = $this->actingAs($this->admin)->get(route('plans.index'));
        $response->assertStatus(200);
        $response->assertSee('باقة الدولار العالمية');
        $response->assertSee('<span class="plan-price-currency">$</span>', false);
        $response->assertSee('150.00 $');

        // التحقق من الظهور في صفحة تفاصيل الخطة
        $showResponse = $this->actingAs($this->admin)->get(route('plans.show', $plan));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('<span class="kpi-unit">$</span>', false);
    }

    public function test_plan_displays_euro_and_riyal_symbols_correctly(): void
    {
        $eurPlan = Plan::create([
            'name' => 'باقة اليورو',
            'slug' => 'euro-plan',
            'status' => 'active',
        ]);
        $eurPrice = $eurPlan->prices()->create([
            'billing_cycle' => 'monthly',
            'price' => 200,
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        $sarPlan = Plan::create([
            'name' => 'باقة الريال',
            'slug' => 'sar-plan',
            'status' => 'active',
        ]);
        $sarPrice = $sarPlan->prices()->create([
            'billing_cycle' => 'monthly',
            'price' => 500,
            'currency' => 'SAR',
            'status' => 'active',
        ]);

        $this->assertEquals('€', $eurPlan->currency_symbol);
        $this->assertEquals('200.00 €', $eurPrice->formattedPrice());

        $this->assertEquals('ر.س', $sarPlan->currency_symbol);
        $this->assertEquals('500.00 ر.س', $sarPrice->formattedPrice());

        $response = $this->actingAs($this->admin)->get(route('plans.index'));
        $response->assertStatus(200);
        $response->assertSee('<span class="plan-price-currency">€</span>', false);
        $response->assertSee('<span class="plan-price-currency">ر.س</span>', false);
    }

    public function test_admin_can_store_plan_with_clean_frontend_features_format(): void
    {
        $limitFeature = Feature::create(['name' => 'عدد الموردين', 'key' => 'max_vendors_clean', 'type' => 'limit']);
        $switchFeature = Feature::create(['name' => 'فحص ذكي', 'key' => 'smart_audit_clean', 'type' => 'feature']);

        $payload = [
            'name' => 'باقة الفرونت المباشرة',
            'slug' => 'direct-frontend-plan',
            'monthly_price' => 150.00,
            'yearly_price' => 1500.00,
            'currency' => 'USD',
            'status' => 'active',
            'description' => 'تجربة إرسال الميزات بالصيغة المباشرة النظيفة من الفرونت إند',
            'features' => [
                $limitFeature->id => ['value' => '75'],
                $switchFeature->id => ['value' => '1'],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('plans.store'), $payload);

        $response->assertRedirect(route('plans.index'));
        $plan = Plan::where('slug', 'direct-frontend-plan')->first();
        $this->assertNotNull($plan);
        $this->assertEquals('75', $plan->getLimit('max_vendors_clean'));
        $this->assertTrue($plan->hasFeature('smart_audit_clean'));
    }

    /**
     * التحقق من حفظ الخطة وميزاتها عندما يترك المستخدم الحد فارغاً وتحويله بأمان إلى '0'.
     */
    public function test_admin_can_store_plan_with_empty_or_null_limits_and_it_defaults_to_zero(): void
    {
        $limitFeature = Feature::create(['name' => 'عدد التقارير', 'key' => 'max_reports', 'type' => 'limit']);

        $payload = [
            'name' => 'باقة بدون حدود محددة',
            'slug' => 'zero-limit-plan',
            'monthly_price' => 100.00,
            'yearly_price' => 1000.00,
            'currency' => 'SAR',
            'status' => 'active',
            'description' => 'تجربة حفظ باقة تم تفريغ الحد العددي فيها',
            'features' => [
                $limitFeature->id => ['value' => ''],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('plans.store'), $payload);

        $response->assertRedirect(route('plans.index'));
        $plan = Plan::where('slug', 'zero-limit-plan')->first();
        $this->assertNotNull($plan);
        $this->assertEquals('0', $plan->getLimit('max_reports'));

        // التأكد من عدم إدراج صف صفري في جدول الربط
        $this->assertDatabaseMissing('plan_features', [
            'plan_id' => $plan->id,
            'feature_id' => $limitFeature->id,
        ]);

        // التأكد من عدم ظهور الميزة في كارت الباقة
        $indexResponse = $this->actingAs($this->admin)->get(route('plans.index'));
        $indexResponse->assertDontSee('عدد التقارير');
    }

    /**
     * التحقق من شاشة إدارة الميزات المخصصة عندما يحذف المستخدم القيمة وتستبعد من الباقة بنجاح.
     */
    public function test_admin_can_update_plan_features_when_user_deletes_value_and_it_saves_as_zero(): void
    {
        $plan = Plan::create([
            'name' => 'باقة التحديث الفوري',
            'slug' => 'instant-update-plan',
            'status' => 'active',
        ]);

        $limitFeature = Feature::create(['name' => 'حد الملفات', 'key' => 'max_files', 'type' => 'limit']);
        $plan->features()->attach($limitFeature->id, ['value' => '50']);

        // محاكاة قيام المستخدم بحذف القيمة وإرسالها فارغة أو null
        $payload = [
            'features' => [
                $limitFeature->id => ['value' => null],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('plans.features.update', $plan), $payload);

        $response->assertRedirect(route('plans.index'));
        $response->assertSessionHas('success');

        // التأكد من فك الارتباط من جدول الربط وعدم حجز صف صفري
        $this->assertDatabaseMissing('plan_features', [
            'plan_id' => $plan->id,
            'feature_id' => $limitFeature->id,
        ]);
        $this->assertEquals('0', $plan->fresh()->getLimit('max_files'));
    }
}

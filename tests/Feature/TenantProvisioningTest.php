<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\User;
use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;
use App\Services\Tenants\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء باقة افتراضية مجانية لاختبارات التسجيل التلقائي
        $plan = Plan::create([
            'name' => 'الباقة الافتراضية',
            'slug' => 'free',
            'description' => 'باقة افتراضية للاختبارات',
            'status' => 'active',
        ]);

        $plan->prices()->create([
            'price' => 0.00,
            'currency' => 'SAR',
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
    }

    /**
     * اختبار إنشاء المنشأة والمدير والاشتراك عبر خدمة TenantService بنجاح.
     */
    public function test_tenant_service_creates_tenant_user_and_subscription(): void
    {
        $plan = Plan::first() ?? Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'description' => 'Test description',
            'status' => 'active',
        ]);

        $price = $plan->prices()->first() ?? $plan->prices()->create([
            'price' => 0.00,
            'currency' => 'SAR',
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $service = app(TenantService::class);

        $tenantData = [
            'name' => 'شركة الرواد للاختبار',
            'slug' => 'alrowad-test',
            'company_cr' => '1010998877',
            'email' => 'info@alrowad-test.sa',
            'phone' => '0501234567',
        ];

        $userData = [
            'name' => 'المدير التجريبي',
            'email' => 'manager@alrowad-test.sa',
            'password' => 'password123',
        ];

        $result = $service->createTenant(
            $tenantData,
            $userData,
            $plan->id
        );

        $this->assertInstanceOf(Tenant::class, $result['tenant']);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertEquals('alrowad-test', $result['tenant']->slug);
        $this->assertEquals($result['tenant']->id, $result['user']->tenant_id);

        // التأكد من إنشاء الاشتراك الرسمي عبر السيرفز
        $subscription = $result['tenant']->currentSubscription;
        $this->assertNotNull($subscription);
        $this->assertEquals($price->id, $subscription->plan_price_id);
        $this->assertTrue($subscription->isActive());

        // تنظيف بيانات الاختبار
        $result['tenant']->forceDelete();
    }

    /**
     * اختبار مسار التسجيل الذاتي عبر الـ API مع هاتف وإيميل المنشأة.
     */
    public function test_api_register_creates_tenant_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'company_name'          => 'شركة سحاب الرقمية',
            'slug'                  => 'sahab-digital-test',
            'company_cr'            => '1010776655',
            'company_email'         => 'contact@sahab-digital-test.sa',
            'phone'                 => '0112233445',
            'name'                  => 'سعود العتيبي',
            'email'                 => 'saud@sahab-digital-test.sa',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'user' => ['id', 'name', 'email'],
                'tenant' => [
                    'id',
                    'name',
                    'slug',
                    'company_cr',
                    'email',
                    'phone',
                    'plan' => ['id', 'name', 'slug', 'limits', 'features'],
                ],
                'locked_features',
            ],
        ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertEquals('contact@sahab-digital-test.sa', $response->json('data.tenant.email'));
        $this->assertEquals('0112233445', $response->json('data.tenant.phone'));
        $this->assertEquals('1010776655', $response->json('data.tenant.company_cr'));
        $this->assertEquals('free', $response->json('data.tenant.plan.slug'));
        $this->assertIsArray($response->json('data.tenant.plan.limits'));
        $this->assertIsArray($response->json('data.tenant.plan.features'));

        // تنظيف
        $tenantId = $response->json('data.tenant.id');
        Tenant::find($tenantId)?->forceDelete();
    }

    /**
     * اختبار مسار تسجيل الدخول وفحص الجلسة وبروفايل المستخدم وبروفايل المنشأة.
     */
    public function test_api_login_returns_token(): void
    {
        // 1. تسجيل أولاً
        $regResponse = $this->postJson('/api/v1/register', [
            'company_name'          => 'شركة تجربة الدخول',
            'slug'                  => 'login-test-co',
            'company_cr'            => '1010998822',
            'company_email'         => 'info@login-test-co.sa',
            'phone'                 => '0119988776',
            'name'                  => 'محمد الدوسري',
            'email'                 => 'm@login-test-co.sa',
            'password'              => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $regResponse->assertStatus(201);

        // 2. محاولة الدخول
        $loginResponse = $this->postJson('/api/v1/login', [
            'email'    => 'm@login-test-co.sa',
            'password' => 'securepass123',
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'user' => ['id', 'name', 'email'],
                'tenant' => [
                    'id',
                    'name',
                    'slug',
                    'company_cr',
                    'email',
                    'phone',
                    'plan' => ['id', 'name', 'slug', 'limits', 'features'],
                ],
                'locked_features',
            ],
        ]);

        $token = $loginResponse->json('data.token');

        // 3. فحص مسار me
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me');

        $meResponse->assertStatus(200);
        $meResponse->assertJsonPath('data.user.email', 'm@login-test-co.sa');
        $meResponse->assertJsonPath('data.tenant.status', 'active');
        $meResponse->assertJsonPath('data.tenant.email', 'info@login-test-co.sa');
        $meResponse->assertJsonPath('data.tenant.phone', '0119988776');
        $this->assertEquals('free', $meResponse->json('data.tenant.plan.slug'));

        // 4. فحص استعراض بروفايل المستخدم الشخصي GET /profile
        $profileShow = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile');

        $profileShow->assertStatus(200);
        $profileShow->assertJsonPath('data.name', 'محمد الدوسري');
        $profileShow->assertJsonPath('data.email', 'm@login-test-co.sa');

        // 5. فحص تحديث بروفايل المستخدم مع تغيير كلمة المرور PUT /profile
        $profileUpdate = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/profile', [
                'name'                  => 'محمد الدوسري المحدث',
                'email'                 => 'm.updated@login-test-co.sa',
                'current_password'      => 'securepass123',
                'password'              => 'newsecurepass456',
                'password_confirmation' => 'newsecurepass456',
            ]);

        $profileUpdate->assertStatus(200);
        $profileUpdate->assertJsonPath('data.name', 'محمد الدوسري المحدث');
        $profileUpdate->assertJsonPath('data.email', 'm.updated@login-test-co.sa');

        // 6. فحص استعراض بروفايل المنشأة / الشركة GET /company
        $companyShow = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/company');

        $companyShow->assertStatus(200);
        $companyShow->assertJsonPath('data.name', 'شركة تجربة الدخول');
        $companyShow->assertJsonPath('data.company_cr', '1010998822');
        $companyShow->assertJsonPath('data.email', 'info@login-test-co.sa');
        $companyShow->assertJsonPath('data.phone', '0119988776');

        // 7. فحص تحديث بروفايل المنشأة PUT /company
        $companyUpdate = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/company', [
                'name'       => 'شركة تجربة الدخول المتطورة',
                'company_cr' => '1010998822',
                'email'      => 'official@login-test-co.sa',
                'phone'      => '0115544332',
            ]);

        $companyUpdate->assertStatus(200);
        $companyUpdate->assertJsonPath('data.name', 'شركة تجربة الدخول المتطورة');
        $companyUpdate->assertJsonPath('data.email', 'official@login-test-co.sa');
        $companyUpdate->assertJsonPath('data.phone', '0115544332');

        // 8. فحص تسجيل الخروج
        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout');

        $logoutResponse->assertStatus(200);
        $logoutResponse->assertJsonPath('success', true);
    }

    /**
     * اختبار مسار استرجاع الباقات والأسعار العام المخصص لصفحة الهبوط.
     */
    public function test_public_api_plans_returns_active_plans_with_prices_and_checklist(): void
    {
        $response = $this->getJson('/api/v1/plans');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'prices' => [
                        'monthly' => ['id', 'price', 'currency', 'currency_symbol', 'billing_cycle'],
                        'yearly',
                    ],
                    'checklist',
                ],
            ],
        ]);

        $firstPlan = $response->json('data.0');
        $this->assertNotEmpty($firstPlan);
        $this->assertEquals('SAR', $firstPlan['prices']['monthly']['currency']);
        $this->assertEquals('ر.س', $firstPlan['prices']['monthly']['currency_symbol']);
        $this->assertIsArray($firstPlan['checklist']);
    }

    /**
     * اختبار دوال الحماية المنطقية المباشرة في موديل Tenant لمنع الثغرات.
     */
    public function test_tenant_feature_gating_logic(): void
    {
        $tenant = Tenant::create([
            'name' => 'شركة فحص الصلاحيات',
            'slug' => 'gating-test-co',
            'email' => 'gate@company.sa',
        ]);

        $plan = Plan::create([
            'name' => 'باقة الصلاحيات',
            'slug' => 'gating-plan',
            'status' => 'active',
        ]);

        $price = $plan->prices()->create([
            'price' => 0.00,
            'currency' => 'SAR',
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        // ميزة مفعلة
        $featActive = Feature::create([
            'name' => 'تصفح الوثائق',
            'key' => 'view_docs',
            'type' => 'feature',
        ]);

        // ميزة مقفلة
        $featLocked = Feature::create([
            'name' => 'تصدير التقارير',
            'key' => 'export_reports',
            'type' => 'feature',
        ]);

        // حد عددي
        $featLimit = Feature::create([
            'name' => 'حد الموردين',
            'key' => 'vendors_limit',
            'type' => 'limit',
        ]);

        // ربط الميزات بالباقة
        $plan->features()->attach([
            $featActive->id => ['value' => '1'],
            $featLocked->id => ['value' => '0'],
            $featLimit->id => ['value' => '5'],
        ]);

        // إنشاء اشتراك
        $tenant->subscriptions()->create([
            'plan_price_id' => $price->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        // التحقق من الحماية المنطقية بالباك إند عبر السمة HasPlanFeatures
        $this->assertTrue($tenant->canUseFeature('view_docs'));
        $this->assertFalse($tenant->canUseFeature('export_reports'));
        $this->assertFalse($tenant->canUseFeature('non_existing_feature'));
        $this->assertEquals(5, $tenant->getFeatureLimit('vendors_limit'));

        // فحص الدوال العامة للحدود hasReachedLimit و canUseMore
        $this->assertFalse($tenant->hasReachedLimit('vendors_limit', 3));
        $this->assertTrue($tenant->hasReachedLimit('vendors_limit', 5));
        $this->assertTrue($tenant->hasReachedLimit('vendors_limit', 6));
        $this->assertTrue($tenant->canUseMore('vendors_limit', 3));
        $this->assertFalse($tenant->canUseMore('vendors_limit', 5));

        // فحص الحدود الديناميكية dynamicLimits
        $dynamicLimits = $plan->dynamicLimits();
        $this->assertArrayHasKey('vendors_limit', $dynamicLimits);
        $this->assertEquals(5, $dynamicLimits['vendors_limit']);

        // التحقق من الميزات المقفلة
        $this->assertContains('export_reports', $tenant->lockedFeatureKeys());
        $this->assertNotContains('view_docs', $tenant->lockedFeatureKeys());
    }

    /**
     * اختبار مسار إغلاق وحذف حساب المنشأة الآمن مع ضوابط الأمان.
     */
    public function test_delete_company_terminates_account_safely(): void
    {
        // 1. تسجيل منشأة جديدة
        $regResponse = $this->postJson('/api/v1/register', [
            'company_name'          => 'شركة تجربة الإغلاق',
            'slug'                  => 'close-test-co',
            'company_cr'            => '1010332211',
            'company_email'         => 'close@test-co.sa',
            'name'                  => 'المالك المودع',
            'email'                 => 'owner@close-test-co.sa',
            'password'              => 'closepass123',
            'password_confirmation' => 'closepass123',
        ]);

        $regResponse->assertStatus(201);
        $token = $regResponse->json('data.token');
        $tenantId = $regResponse->json('data.tenant.id');

        // 2. محاولة الحذف بكلمة مرور خاطئة -> 422
        $wrongPass = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/company', [
                'current_password'  => 'wrong_password',
                'confirmation_name' => 'شركة تجربة الإغلاق',
            ]);
        $wrongPass->assertStatus(422);

        // 3. محاولة الحذف باسم تأكيد غير مطابق -> 422
        $wrongName = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/company', [
                'current_password'  => 'closepass123',
                'confirmation_name' => 'اسم شركة مختلف تماماً',
            ]);
        $wrongName->assertStatus(422);

        // 4. الحذف الناجح بعد استيفاء الأمان
        $successDelete = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/company', [
                'current_password'  => 'closepass123',
                'confirmation_name' => 'شركة تجربة الإغلاق',
            ]);

        $successDelete->assertStatus(200);
        $successDelete->assertJsonPath('success', true);

        // 5. التحقق من الحذف الناعم للمنشأة
        $deletedTenant = Tenant::withTrashed()->find($tenantId);
        $this->assertNotNull($deletedTenant);
        $this->assertTrue($deletedTenant->trashed());
        $this->assertEquals('closed', $deletedTenant->status);

        // 6. التحقق من إبطال التوكن ورفض الوصول فوراً
        app('auth')->forgetGuards();
        $meAfterDelete = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me');
        $meAfterDelete->assertStatus(401);
    }

    /**
     * اختبار دورة إدارة الاشتراكات للعميل (عرض، ترقية، وإلغاء).
     */
    public function test_api_tenant_subscription_management_flow(): void
    {
        // 1. إنشاء باقة مدفوعة للاختبار
        $proPlan = Plan::create([
            'name'        => 'الباقة الاحترافية',
            'slug'        => 'pro',
            'description' => 'باقة متقدمة للاختبارات',
            'status'      => 'active',
        ]);

        $proPlan->prices()->create([
            'price'         => 299.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);

        $proYearlyPrice = $proPlan->prices()->create([
            'price'         => 2990.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'yearly',
            'status'        => 'active',
        ]);

        // 2. تسجيل منشأة جديدة (تكون على الباقة المجانية تلقائياً)
        $regResponse = $this->postJson('/api/v1/register', [
            'company_name'          => 'شركة تجربة الاشتراكات',
            'slug'                  => 'sub-test-co',
            'company_cr'            => '1010332211',
            'company_email'         => 'sub@test-co.sa',
            'phone'                 => '0112223334',
            'name'                  => 'مدير الاشتراكات',
            'email'                 => 'manager@sub-test-co.sa',
            'password'              => 'subpass123',
            'password_confirmation' => 'subpass123',
        ]);

        $regResponse->assertStatus(201);
        $token = $regResponse->json('data.token');

        // 3. فحص استعراض تفاصيل الاشتراك الحالي على الباقة المجانية
        $showResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/subscription');

        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('success', true);
        $showResponse->assertJsonPath('data.tenant.plan.slug', 'free');
        $showResponse->assertJsonPath('data.subscription.status', 'active');
        $showResponse->assertJsonPath('data.subscription.price', '0.00');
        $this->assertNotNull($showResponse->json('data.subscription.id'));
        $this->assertNull($showResponse->json('data.subscription.current_period_end'));

        // 4. محاولة إلغاء الباقة المجانية -> يجب أن تفشل بكود 422
        $cancelFreeResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/subscription/cancel');

        $cancelFreeResponse->assertStatus(422);
        $cancelFreeResponse->assertJsonPath('success', false);

        // 5. محاولة الترقية لنفس الباقة الحالية -> يجب أن تفشل بكود 422
        $freePlanId = $regResponse->json('data.tenant.plan.id');
        $redundantUpgrade = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/subscription/upgrade', [
                'plan_id'       => $freePlanId,
                'billing_cycle' => 'monthly',
            ]);

        $redundantUpgrade->assertStatus(422);

        // 6. الترقية الناجحة للباقة الاحترافية بدورة سنوية
        $successUpgrade = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/subscription/upgrade', [
                'plan_id'       => $proPlan->id,
                'billing_cycle' => 'yearly',
            ]);

        $successUpgrade->assertStatus(200);
        $successUpgrade->assertJsonPath('success', true);
        $successUpgrade->assertJsonPath('data.tenant.plan.slug', 'pro');
        $successUpgrade->assertJsonPath('data.subscription.status', 'active');
        $successUpgrade->assertJsonPath('data.subscription.billing_cycle', 'yearly');
        $successUpgrade->assertJsonPath('data.subscription.price', '2990.00');
        $this->assertNotNull($successUpgrade->json('data.subscription.current_period_end'));

        // 7. استعراض تفاصيل الاشتراك بعد الترقية
        $showAfterUpgrade = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/subscription');

        $showAfterUpgrade->assertStatus(200);
        $showAfterUpgrade->assertJsonPath('data.tenant.plan.slug', 'pro');
        $showAfterUpgrade->assertJsonPath('data.subscription.billing_cycle', 'yearly');

        // 8. إلغاء الاشتراك المدفوع والعودة التلقائية للباقة المجانية
        $cancelPaidResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/subscription/cancel');

        $cancelPaidResponse->assertStatus(200);
        $cancelPaidResponse->assertJsonPath('success', true);
        $cancelPaidResponse->assertJsonPath('data.tenant.plan.slug', 'free');
        $cancelPaidResponse->assertJsonPath('data.subscription.status', 'canceled');

        // 9. التأكد من أن المنشأة عادت للباقة المجانية عبر نقطة العرض
        $showFinal = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/subscription');

        $showFinal->assertStatus(200);
        $showFinal->assertJsonPath('data.tenant.plan.slug', 'free');
    }

    /**
     * اختبار دوال ونطاقات isFree و isPaid في موديل Plan.
     */
    public function test_plan_model_is_free_and_is_paid_and_scopes(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();
        $this->assertTrue($freePlan->isFree());
        $this->assertFalse($freePlan->isPaid());

        $paidPlan = Plan::create([
            'name'        => 'باقة ممتازة',
            'slug'        => 'premium',
            'description' => 'باقة مدفوعة',
            'status'      => 'active',
        ]);
        $paidPlan->prices()->create([
            'price'         => 500.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);

        $this->assertFalse($paidPlan->isFree());
        $this->assertTrue($paidPlan->isPaid());

        $this->assertEquals(1, Plan::free()->count());
        $this->assertEquals(1, Plan::paid()->count());
    }

    /**
     * اختبار إنشاء منشأة بدورة سنوية واحتساب تاريخ الانتهاء لعام كامل.
     */
    public function test_admin_can_create_tenant_with_yearly_billing_cycle(): void
    {
        $paidPlan = Plan::create([
            'name'        => 'باقة سنوية',
            'slug'        => 'yearly-tier',
            'description' => 'باقة سنوية',
            'status'      => 'active',
        ]);
        $paidPlan->prices()->create([
            'price'         => 1200.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'yearly',
            'status'        => 'active',
        ]);

        $service = app(TenantService::class);
        $result = $service->createTenant(
            [
                'name'       => 'شركة السنوي',
                'slug'       => 'yearly-company',
                'company_cr' => '1010998877',
                'email'      => 'info@yearly.sa',
                'phone'      => '0555555555',
                'status'     => 'active',
            ],
            [
                'name'     => 'مدير السنوي',
                'email'    => 'admin@yearly.sa',
                'password' => 'password123',
            ],
            $paidPlan->id,
            'yearly'
        );

        $tenant = $result['tenant'];
        $sub = $tenant->currentSubscription;

        $this->assertNotNull($sub);
        $this->assertEquals('active', $sub->status);
        $this->assertEquals('yearly', $sub->planPrice->billing_cycle);
        $this->assertNotNull($sub->current_period_end);
        $this->assertTrue($sub->current_period_end->isFuture());
        // التأكد من أن الفرق الزمني يقارب سنة (أكثر من 360 يوماً)
        $this->assertGreaterThan(360, now()->diffInDays($sub->current_period_end));
    }

    /**
     * اختبار مزامنة الاشتراك عند تعديل باقة المنشأة من لوحة التحكم.
     */
    public function test_admin_can_update_tenant_and_sync_subscription(): void
    {
        $admin = \App\Modules\Web\Models\Admin::create([
            'name'     => 'مدير المنصة',
            'email'    => 'platform_admin@test.sa',
            'password' => bcrypt('admin_pass_123'),
        ]);

        $freePlan = Plan::where('slug', 'free')->first();
        $paidPlan = Plan::create([
            'name'        => 'باقة الترقية',
            'slug'        => 'upgrade-tier',
            'description' => 'باقة ترقية',
            'status'      => 'active',
        ]);
        $paidPlan->prices()->create([
            'price'         => 499.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);
        $paidPlan->prices()->create([
            'price'         => 4990.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'yearly',
            'status'        => 'active',
        ]);

        $service = app(TenantService::class);
        $result = $service->createTenant(
            [
                'name'       => 'شركة الترقية',
                'slug'       => 'upgrade-comp',
                'company_cr' => '1010112233',
                'email'      => 'upgrade@company.sa',
                'phone'      => '0511111111',
                'status'     => 'active',
            ],
            [
                'name'     => 'مدير الترقية',
                'email'    => 'admin@upgrade.sa',
                'password' => 'secret123',
            ],
            $freePlan->id,
            'monthly'
        );

        $tenant = $result['tenant'];
        $this->assertNull($tenant->currentSubscription->current_period_end);

        // ترقية المنشأة عبر المتحكم باستخدام دورة سنوية
        $response = $this->actingAs($admin)
            ->put(route('tenants.update', $tenant), [
                'name'          => 'شركة الترقية المحدثة',
                'slug'          => 'upgrade-comp',
                'company_cr'    => '1010112233',
                'email'         => 'upgrade@company.sa',
                'phone'         => '0511111111',
                'status'        => 'active',
                'plan_id'       => $paidPlan->id,
                'billing_cycle' => 'yearly',
            ]);

        $response->assertRedirect(route('tenants.show', $tenant));
        $response->assertSessionHas('success');

        $tenant->refresh();
        $sub = $tenant->currentSubscription;

        $this->assertEquals($paidPlan->id, $tenant->plan_id);
        $this->assertEquals('active', $sub->status);
        $this->assertEquals('yearly', $sub->planPrice->billing_cycle);
        $this->assertNotNull($sub->current_period_end);
        $this->assertGreaterThan(360, now()->diffInDays($sub->current_period_end));
    }
}

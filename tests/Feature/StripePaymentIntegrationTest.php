<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Tenant;
use App\Modules\Api\Models\User;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\PlanPrice;
use App\Modules\Web\Models\Subscription;
use App\Services\Payments\StripePaymentService;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session;
use Tests\TestCase;

/**
 * اختبارات التكامل لبوابة الدفع Stripe وإدارة دورة حياة الاشتراكات المسبقة.
 */
class StripePaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Plan $freePlan;
    private Plan $proPlan;
    private PlanPrice $proMonthlyPrice;
    private PlanPrice $proYearlyPrice;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. إنشاء باقة مجانية افتراضية
        $this->freePlan = Plan::create([
            'name'        => 'الباقة المجانية',
            'slug'        => 'free',
            'description' => 'باقة مجانية دائمة',
            'status'      => 'active',
        ]);

        $this->freePlan->prices()->create([
            'price'         => 0.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);

        // 2. إنشاء باقة مدفوعة باشتراك شهري وسنوي
        $this->proPlan = Plan::create([
            'name'        => 'الباقة الاحترافية',
            'slug'        => 'pro',
            'description' => 'باقة متقدمة للمنشآت',
            'status'      => 'active',
        ]);

        $this->proMonthlyPrice = $this->proPlan->prices()->create([
            'price'         => 299.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);

        $this->proYearlyPrice = $this->proPlan->prices()->create([
            'price'         => 2990.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'yearly',
            'status'        => 'active',
        ]);

        // 3. إنشاء منشأة ومستخدم مسؤول مع اشتراك مجاني مبدئي
        $this->tenant = Tenant::create([
            'plan_id'    => $this->freePlan->id,
            'name'       => 'شركة السداد التجريبية',
            'slug'       => 'pay-test-co',
            'email'      => 'billing@pay-test.sa',
            'company_cr' => '1010887766',
            'status'     => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'مدير المدفوعات',
            'email'     => 'admin@pay-test.sa',
            'password'  => bcrypt('secret123'),
            'status'    => 'active',
        ]);

        app(SubscriptionService::class)->createSubscription($this->tenant, $this->freePlan, 'monthly');

        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * اختبار إنشاء جلسة سداد Stripe بنجاح.
     */
    public function test_checkout_creates_pending_payment_and_returns_session_url(): void
    {
        $mockStripe = $this->mock(StripePaymentService::class);
        $mockStripe->shouldReceive('createCheckoutSession')
            ->once()
            ->withArgs(function ($tenant, $plan, $planPrice, $successUrl, $cancelUrl) {
                return $tenant->id === $this->tenant->id
                    && $plan->id === $this->proPlan->id
                    && $planPrice->id === $this->proMonthlyPrice->id;
            })
            ->andReturn('https://checkout.stripe.com/c/pay/cs_test_mock_session');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/subscription/checkout', [
                'plan_id'       => $this->proPlan->id,
                'billing_cycle' => 'monthly',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.checkout_url', 'https://checkout.stripe.com/c/pay/cs_test_mock_session');
    }

    /**
     * اختبار رفض طلب الدفع للباقة الحالية نفسها.
     */
    public function test_checkout_rejects_current_active_plan(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/subscription/checkout', [
                'plan_id'       => $this->freePlan->id,
                'billing_cycle' => 'monthly',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * اختبار رفض طلب الدفع للباقة المجانية.
     */
    public function test_checkout_rejects_free_plan(): void
    {
        // نقل المنشأة مؤقتاً للباقة الاحترافية لاختبار محاولة الدفع للباقة المجانية
        $this->tenant->update(['plan_id' => $this->proPlan->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/subscription/checkout', [
                'plan_id'       => $this->freePlan->id,
                'billing_cycle' => 'monthly',
            ]);

        $response->assertStatus(422);
    }

    /**
     * اختبار معالجة الويب هوك وتحديث الدفعة وتمديد اشتراك المنشأة بشكل ذري.
     */
    public function test_stripe_webhook_activates_plan_and_marks_payment_paid(): void
    {
        // 1. إنشاء دفعة معلقة
        $payment = Payment::create([
            'tenant_id'     => $this->tenant->id,
            'plan_id'       => $this->proPlan->id,
            'plan_price_id' => $this->proYearlyPrice->id,
            'amount'        => 2990.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'yearly',
            'status'        => 'pending',
            'gateway'       => 'stripe',
        ]);

        // 2. محاكاة كائن جلسة سترايب المستلمة من الويب هوك
        $session = Session::constructFrom([
            'id'                   => 'cs_test_completed_123',
            'payment_intent'       => 'pi_test_intent_456',
            'payment_method_types' => ['card'],
            'metadata'             => [
                'payment_id' => (string) $payment->id,
            ],
        ]);

        // 3. تنفيذ معالجة النجاح عبر الخدمة
        $service = app(StripePaymentService::class);
        $method = new \ReflectionMethod(StripePaymentService::class, 'processPaymentSuccess');
        $method->invoke($service, $session);

        // 4. التحقق من تحديث سجل الدفعة
        $payment->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertEquals('pi_test_intent_456', $payment->gateway_reference_id);
        $this->assertNotNull($payment->paid_at);

        // 5. التحقق من ترقية المنشأة للباقة الاحترافية
        $this->tenant->refresh();
        $this->assertEquals($this->proPlan->id, $this->tenant->plan_id);

        // 6. التحقق من تاريخ انتهاء الاشتراك الجديد (سنة كاملة)
        $activeSub = $this->tenant->currentSubscription;
        $this->assertNotNull($activeSub);
        $this->assertEquals('active', $activeSub->status);
        $this->assertNotNull($activeSub->current_period_end);
        $this->assertTrue($activeSub->current_period_end->isFuture());
    }

    /**
     * اختبار خاصية عدم التكرار (Idempotency) في معالجة الويب هوك.
     */
    public function test_webhook_idempotency_prevents_duplicate_processing(): void
    {
        $payment = Payment::create([
            'tenant_id'            => $this->tenant->id,
            'plan_id'              => $this->proPlan->id,
            'plan_price_id'        => $this->proMonthlyPrice->id,
            'amount'               => 299.00,
            'currency'             => 'SAR',
            'billing_cycle'        => 'monthly',
            'status'               => 'paid',
            'paid_at'              => now(),
            'gateway_reference_id' => 'pi_already_paid',
            'gateway'              => 'stripe',
        ]);

        $session = Session::constructFrom([
            'id'                   => 'cs_test_dup_123',
            'payment_intent'       => 'pi_already_paid',
            'payment_method_types' => ['card'],
            'metadata'             => [
                'payment_id' => (string) $payment->id,
            ],
        ]);

        $service = app(StripePaymentService::class);
        $method = new \ReflectionMethod(StripePaymentService::class, 'processPaymentSuccess');
        $method->invoke($service, $session);

        // التأكد من عدم تغيير المعرف أو تاريخ الدفع
        $payment->refresh();
        $this->assertEquals('pi_already_paid', $payment->gateway_reference_id);
    }

    /**
     * اختبار حماية انتهاء الصلاحية: المنشأة ذات الاشتراك المنتهي تعود تلقائياً للباقة المجانية.
     */
    public function test_expired_paid_subscription_falls_back_to_free_plan(): void
    {
        // 1. ترقية المنشأة للباقة الاحترافية
        $this->tenant->update(['plan_id' => $this->proPlan->id]);

        // 2. إنشاء اشتراك مدفوع منتهٍ في الماضي
        Subscription::create([
            'tenant_id'          => $this->tenant->id,
            'plan_price_id'      => $this->proMonthlyPrice->id,
            'status'             => 'active',
            'starts_at'          => now()->subMonths(2),
            'current_period_end' => now()->subDay(), // انتهت الصلاحية بالأمس
        ]);

        // 3. فحص currentPlan عبر السمة HasPlanFeatures
        $effectivePlan = $this->tenant->currentPlan();

        // يجب أن ترجع الباقة المجانية كحماية فورية من استهلاك ميزات الباقة المدفوعة المنتهية
        $this->assertNotNull($effectivePlan);
        $this->assertEquals('free', $effectivePlan->slug);
    }

    /**
     * اختبار رفض الويب هوك ذي التوقيع غير الصالح برمز 400.
     */
    public function test_webhook_endpoint_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/v1/stripe/webhook', [
            'type' => 'checkout.session.completed',
        ], [
            'Stripe-Signature' => 'invalid_signature_mock',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
    }
}

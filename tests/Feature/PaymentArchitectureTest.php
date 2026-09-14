<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Tenant;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\PlanPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * فحص البنية التحتية لجدول وموديل المدفوعات (Payment Architecture Tests).
 */
class PaymentArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Plan $plan;
    private PlanPrice $planPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'       => 'شركة التقنية المتقدمة',
            'slug'       => 'advanced-tech',
            'email'      => 'info@advancedtech.sa',
            'company_cr' => '1010998877',
            'status'     => 'active',
        ]);

        $this->plan = Plan::create([
            'name'        => 'باقة النمو المتقدمة',
            'slug'        => 'growth-pro',
            'description' => 'باقة الشركات المتوسطة',
            'status'      => 'active',
        ]);

        $this->planPrice = $this->plan->prices()->create([
            'price'         => '499.00',
            'currency'      => 'SAR',
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);
    }

    public function test_payments_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('payments'));

        $expectedColumns = [
            'id',
            'tenant_id',
            'plan_id',
            'plan_price_id',
            'amount',
            'currency',
            'billing_cycle',
            'status',
            'gateway',
            'gateway_reference_id',
            'payment_method',
            'receipt_url',
            'paid_at',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('payments', $column),
                "Missing expected column: {$column} in payments table"
            );
        }
    }

    public function test_payment_relationships_and_tenant_association(): void
    {
        $payment = Payment::create([
            'tenant_id'     => $this->tenant->id,
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'amount'        => '499.00',
            'currency'      => 'SAR',
            'billing_cycle' => 'monthly',
            'status'        => 'pending',
            'gateway'       => 'stripe',
        ]);

        // التحقق من وصول الفاتورة للمنشأة والباقة
        $this->assertEquals($this->tenant->id, $payment->tenant->id);
        $this->assertEquals($this->plan->id, $payment->plan->id);
        $this->assertEquals($this->planPrice->id, $payment->planPrice->id);

        // التحقق من علاقة payments في موديل Tenant
        $this->assertCount(1, $this->tenant->payments);
        $this->assertEquals($payment->id, $this->tenant->payments->first()->id);

        // التحقق من علاقة payments في موديل Plan
        $this->assertCount(1, $this->plan->payments);
    }

    public function test_payment_helper_methods_and_formatting(): void
    {
        $pendingPayment = Payment::create([
            'tenant_id'     => $this->tenant->id,
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'amount'        => '499.00',
            'currency'      => 'SAR',
            'billing_cycle' => 'monthly',
            'status'        => 'pending',
            'gateway'       => 'stripe',
        ]);

        $this->assertTrue($pendingPayment->isPending());
        $this->assertFalse($pendingPayment->isPaid());
        $this->assertEquals('بانتظار السداد', $pendingPayment->statusLabelArabic());
        $this->assertEquals('شهري', $pendingPayment->billingCycleArabic());
        $this->assertEquals('499.00 ر.س', $pendingPayment->formattedAmount());

        // تحويل الحالة إلى مدفوعة
        $pendingPayment->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertTrue($pendingPayment->isPaid());
        $this->assertEquals('مسددة', $pendingPayment->statusLabelArabic());
    }

    public function test_payment_lifecycle_creation_and_update(): void
    {
        // 1. تسجيل دفعة معلقة مباشرة
        $payment = Payment::create([
            'tenant_id'     => $this->tenant->id,
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'amount'        => $this->planPrice->price,
            'currency'      => $this->planPrice->currency,
            'billing_cycle' => $this->planPrice->billing_cycle,
            'status'        => 'pending',
            'gateway'       => 'stripe',
        ]);

        $this->assertNotNull($payment->id);
        $this->assertEquals('pending', $payment->status);

        // 2. تحديث الدفعة بعد سداد سترايب بسطر مباشر وبسيط
        $payment->update([
            'status'               => 'paid',
            'gateway_reference_id' => 'cs_test_sample_123',
            'payment_method'       => 'card',
            'receipt_url'          => 'https://pay.stripe.com/receipts/sample',
            'paid_at'              => now(),
        ]);

        $this->assertTrue($payment->isPaid());
        $this->assertEquals('cs_test_sample_123', $payment->gateway_reference_id);
        $this->assertEquals('card', $payment->payment_method);
        $this->assertEquals('https://pay.stripe.com/receipts/sample', $payment->receipt_url);
    }
}

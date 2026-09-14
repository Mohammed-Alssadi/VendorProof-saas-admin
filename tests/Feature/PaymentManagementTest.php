<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Tenant;
use App\Modules\Web\Models\Admin;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\PlanPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Tenant $tenant;
    protected Plan $plan;
    protected PlanPrice $planPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name'     => 'مدير النظام',
            'email'    => 'admin@vendorproof.test',
            'password' => bcrypt('password123'),
        ]);

        $this->plan = Plan::create([
            'name'        => 'باقة المؤسسات',
            'slug'        => 'enterprise',
            'description' => 'باقة متقدمة',
            'status'      => 'active',
        ]);

        $this->planPrice = PlanPrice::create([
            'plan_id'       => $this->plan->id,
            'price'         => 1499.00,
            'currency'      => 'SAR',
            'billing_cycle' => 'monthly',
            'is_active'     => true,
        ]);

        $this->tenant = Tenant::create([
            'name'       => 'شركة التقنية المتقدمة',
            'slug'       => 'advanced-tech',
            'company_cr' => '1010998877',
            'email'      => 'tenant@test.com',
            'phone'      => '0501234567',
            'status'     => 'active',
            'plan_id'    => $this->plan->id,
        ]);
    }

    public function test_guest_cannot_access_payments_page()
    {
        $response = $this->get(route('payments.index'));

        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_payments_index()
    {
        Payment::create([
            'tenant_id'            => $this->tenant->id,
            'plan_id'              => $this->plan->id,
            'plan_price_id'        => $this->planPrice->id,
            'amount'               => 1499.00,
            'currency'             => 'SAR',
            'billing_cycle'        => 'monthly',
            'status'               => 'paid',
            'gateway'              => 'stripe',
            'gateway_reference_id' => 'pi_test_123456',
            'paid_at'              => Carbon::now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertSee('المدفوعات');
        $response->assertSee('pi_test_123456');
        $response->assertSee('شركة التقنية المتقدمة');
        $response->assertSee('1,499.00 ر.س');
    }

    public function test_payments_table_has_filter_attributes_for_client_side_filtering()
    {
        Payment::create([
            'tenant_id'            => $this->tenant->id,
            'plan_id'              => $this->plan->id,
            'plan_price_id'        => $this->planPrice->id,
            'amount'               => 1499.00,
            'currency'             => 'SAR',
            'billing_cycle'        => 'monthly',
            'status'               => 'paid',
            'gateway'              => 'stripe',
            'gateway_reference_id' => 'pi_paid_reference',
            'paid_at'              => Carbon::now(),
        ]);

        Payment::create([
            'tenant_id'            => $this->tenant->id,
            'plan_id'              => $this->plan->id,
            'plan_price_id'        => $this->planPrice->id,
            'amount'               => 299.00,
            'currency'             => 'SAR',
            'billing_cycle'        => 'monthly',
            'status'               => 'pending',
            'gateway'              => 'stripe',
            'gateway_reference_id' => 'pi_pending_reference',
        ]);

        $response = $this->actingAs($this->admin)->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertSee('pi_paid_reference');
        $response->assertSee('pi_pending_reference');
        $response->assertSee('data-status="paid"', false);
        $response->assertSee('data-status="pending"', false);
        $response->assertSee('data-cycle="monthly"', false);
    }

    public function test_admin_sees_empty_state_when_no_payments_exist()
    {
        $response = $this->actingAs($this->admin)->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertSee('table-empty-row');
        $response->assertSee('لا توجد عمليات دفع مسجلة');
        $response->assertSee(route('subscriptions.index'));
    }

    public function test_payments_page_includes_client_side_filters_and_script()
    {
        $response = $this->actingAs($this->admin)->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertSee('id="paymentSearchInput"', false);
        $response->assertSee('id="paymentStatusFilter"', false);
        $response->assertSee('id="paymentCycleFilter"', false);
        $response->assertSee('table-filters.js');
    }

    public function test_admin_can_view_single_payment_show()
    {
        $payment = Payment::create([
            'tenant_id'            => $this->tenant->id,
            'plan_id'              => $this->plan->id,
            'plan_price_id'        => $this->planPrice->id,
            'amount'               => 1499.00,
            'currency'             => 'SAR',
            'billing_cycle'        => 'monthly',
            'status'               => 'paid',
            'gateway'              => 'stripe',
            'gateway_reference_id' => 'pi_show_test_77',
            'paid_at'              => Carbon::now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('payments.show', $payment));

        $response->assertStatus(200);
        $response->assertSee('تفاصيل الفاتورة');
        $response->assertSee('pi_show_test_77');
    }
}

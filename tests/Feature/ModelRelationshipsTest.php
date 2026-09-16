<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Modules\Api\Models\User;
use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Models\PlanPrice;
use App\Modules\Web\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبارات علاقات نماذج Eloquent البرمجية والدوال المساعدة الذكية (Eloquent Relationships & Models Test).
 *
 * تغطي التحقق من علاقات المشتركين والمستخدمين، وعلاقات الباقات بالأسعار والميزات والحدود التشغيلية،
 * وسلامة عمل دوال الفحص المساعدة (hasFeature, getLimit, isUnlimited, getLimitDisplay).
 */
class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * اختبار إنشاء منشأة ومستخدمين واسترجاع العلاقات البرمجية عبر Eloquent.
     */
    public function test_tenant_and_user_eloquent_relationships(): void
    {
        $tenant = Tenant::create([
            'name' => 'شركة الحلول السحابية',
            'slug' => 'cloud-solutions',
            'email' => 'contact@cloudsolutions.sa',
            'company_cr' => '1010998877',
            'status' => 'active',
        ]);

        $user1 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'محمد القحطاني',
            'email' => 'mohammed@cloudsolutions.sa',
            'password' => 'secret1234',
            'status' => 'active',
        ]);

        $user2 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'سارة العتيبي',
            'email' => 'sara@cloudsolutions.sa',
            'password' => 'secret1234',
            'status' => 'active',
        ]);

        // اختبار علاقة Tenant -> users
        $this->assertCount(2, $tenant->users);
        $this->assertTrue($tenant->users->contains($user1));
        $this->assertTrue($tenant->users->contains($user2));

        // اختبار علاقة User -> tenant
        $this->assertInstanceOf(Tenant::class, $user1->tenant);
        $this->assertEquals($tenant->id, $user1->tenant->id);
        $this->assertEquals('شركة الحلول السحابية', $user1->tenant->name);
    }

    /**
     * اختبار إنشاء باقة وأسعار ومزايا متعددة واسترجاع الدوال المساعدة.
     */
    public function test_plan_features_and_pricing_relationships(): void
    {
        // 1. إنشاء باقة
        $plan = Plan::create([
            'name' => 'الباقة الاحترافية (Pro)',
            'slug' => 'pro',
            'description' => 'باقة متقدمة للمنشآت المتنامية',
            'status' => 'active',
        ]);

        // 2. إنشاء أسعار (شهري وسنوي)
        $monthlyPrice = PlanPrice::create([
            'plan_id' => $plan->id,
            'price' => 499.00,
            'currency' => 'SAR',
            'billing_cycle' => 'monthly',
        ]);

        $yearlyPrice = PlanPrice::create([
            'plan_id' => $plan->id,
            'price' => 4990.00,
            'currency' => 'SAR',
            'billing_cycle' => 'yearly',
        ]);

        // 3. إنشاء مزايا
        $feature1 = Feature::create([
            'name' => 'عدد الموردين المسموح',
            'key' => 'max_vendors',
            'type' => 'limit',
        ]);

        $feature2 = Feature::create([
            'name' => 'التحقق التلقائي من الوثائق',
            'key' => 'auto_doc_verification',
            'type' => 'feature',
        ]);

        $feature3 = Feature::create([
            'name' => 'سعة التخزين',
            'key' => 'storage_space',
            'type' => 'limit',
        ]);

        // 4. ربط المزايا بالباقة
        $plan->features()->attach($feature1->id, ['value' => '100']);
        $plan->features()->attach($feature2->id, ['value' => '1']);
        $plan->features()->attach($feature3->id, ['value' => '-1']); // غير محدود

        // اختبار علاقات الأسعار
        $this->assertCount(2, $plan->prices);
        $this->assertEquals(499.00, $plan->monthlyPrice->price);
        $this->assertEquals('499.00 ر.س', $monthlyPrice->formattedPrice());
        $this->assertEquals('ر.س', $monthlyPrice->currency_symbol);
        $this->assertEquals(4990.00, $plan->yearlyPrice->price);

        // اختبار علاقات المزايا
        $this->assertCount(3, $plan->features);

        // اختبار الدوال المساعدة الذكية في موديل Plan
        $this->assertTrue($plan->hasFeature('auto_doc_verification'));
        $this->assertEquals('100', $plan->getLimit('max_vendors'));
        $this->assertTrue($plan->isUnlimited('storage_space'));
        $this->assertFalse($plan->isUnlimited('max_vendors'));

        // اختبار دالة العرض الأنيق (منع الرقم السالب -1)
        $this->assertEquals('غير محدود', $plan->getLimitDisplay('storage_space'));
        $this->assertEquals('100', $plan->getLimitDisplay('max_vendors'));

        // اختبار دوال موديل Feature
        $this->assertTrue($feature1->isLimit());
        $this->assertFalse($feature1->isFeature());
        $this->assertTrue($feature2->isFeature());
        $this->assertFalse($feature2->isLimit());
    }

    /**
     * اختبار دورة اشتراك المنشأة وحالة النشاط.
     */
    public function test_tenant_subscription_relationship_and_status(): void
    {
        $tenant = Tenant::create([
            'name' => 'شركة الرائد للتجارة',
            'slug' => 'alraed-trading',
            'email' => 'admin@alraed.sa',
            'status' => 'active',
        ]);

        $plan = Plan::create([
            'name' => 'باقة الأساسيات',
            'slug' => 'basic',
            'status' => 'active',
        ]);

        $price = PlanPrice::create([
            'plan_id' => $plan->id,
            'price' => 199.00,
            'currency' => 'SAR',
            'billing_cycle' => 'monthly',
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_price_id' => $price->id,
            'status' => 'active',
            'starts_at' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // اختبار علاقات الاشتراك
        $this->assertEquals($tenant->id, $subscription->tenant->id);
        $this->assertEquals($price->id, $subscription->planPrice->id);
        $this->assertEquals($plan->id, $subscription->plan->id);

        // اختبار دوال الفحص في موديل Subscription
        $this->assertTrue($subscription->isActive());
        $this->assertFalse($subscription->onTrial());

        // اختبار علاقة currentSubscription في موديل Tenant
        $this->assertNotNull($tenant->currentSubscription);
        $this->assertEquals($subscription->id, $tenant->currentSubscription->id);
    }
}

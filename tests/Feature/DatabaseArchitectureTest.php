<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * اختبارات البنية الهيكلية المعمارية لقاعدة البيانات (Database Architecture Test).
 *
 * تغطي التحقق من وجود كافة جداول المنظومة (المشرفين، المنشآت، المستخدمين، الخطط، الميزات، الاشتراكات)،
 * وضمان تطبيق مفاتيح UUID، وقيد فريد للبريد الإلكتروني على مستوى المنشأة، وسلامة تدفق الفوترة.
 */
class DatabaseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * اختبار وجود كافة جداول المنصة المعمارية الأساسية.
     */
    public function test_all_saas_architecture_tables_exist(): void
    {
        $expectedTables = [
            'admins',
            'tenants',
            'users',
            'plans',
            'features',
            'plan_features',
            'plan_prices',
            'subscriptions',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "جدول {$table} غير موجود في قاعدة البيانات!"
            );
        }
    }

    /**
     * اختبار إنشاء مستأجر ومستخدم مربوط عبر المفتاح الأجنبي UUID.
     */
    public function test_tenant_and_user_can_be_created_with_uuid(): void
    {
        $tenantId = (string) Str::uuid();
        $userId = (string) Str::uuid();

        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'شركة سحاب التقنية',
            'slug' => 'sahab-tech',
            'email' => 'contact@sahabtech.sa',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => $userId,
            'tenant_id' => $tenantId,
            'name' => 'خالد العتيبي',
            'email' => 'khaled@sahabtech.sa',
            'password' => bcrypt('password123'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('tenants', ['id' => $tenantId, 'slug' => 'sahab-tech']);
        $this->assertDatabaseHas('users', ['id' => $userId, 'tenant_id' => $tenantId]);
    }

    /**
     * اختبار قاعدة تفرد البريد لكل مستأجر:
     * السماح لنفس البريد في مستأجرين مختلفين، ومنعه لنفس المستأجر.
     */
    public function test_email_is_unique_per_tenant_scope(): void
    {
        $tenantOne = (string) Str::uuid();
        $tenantTwo = (string) Str::uuid();

        DB::table('tenants')->insert([
            ['id' => $tenantOne, 'name' => 'شركة أ', 'slug' => 'company-a', 'email' => 'info@a.sa', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $tenantTwo, 'name' => 'شركة ب', 'slug' => 'company-b', 'email' => 'info@b.sa', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // نفس البريد في مستأجرين مختلفين -> ينجح
        DB::table('users')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantOne,
            'name' => 'سالم',
            'email' => 'salem@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantTwo,
            'name' => 'سالم',
            'email' => 'salem@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseCount('users', 2);

        // محاولة إدخال نفس البريد لنفس المستأجر (tenantOne) -> يجب أن يفشل بقيد الفهرس الفريد
        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantOne,
            'name' => 'سالم مكرر',
            'email' => 'salem@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * اختبار منظومة الباقات والأسعار واشتراك المستأجرين.
     */
    public function test_plans_pricing_and_subscription_flow(): void
    {
        $tenantId = (string) Str::uuid();
        $subscriptionId = (string) Str::uuid();

        // 1. إنشاء مستأجر
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'منشأة الأفق',
            'slug' => 'alofooq',
            'email' => 'admin@alofooq.sa',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. إنشاء باقة
        $planId = DB::table('plans')->insertGetId([
            'name' => 'باقة الاحترافية (Pro)',
            'slug' => 'pro',
            'description' => 'باقة متقدمة للمنشآت الكبرى',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. إنشاء سعر للباقة (شهري)
        $planPriceId = DB::table('plan_prices')->insertGetId([
            'plan_id' => $planId,
            'price' => 199.00,
            'currency' => 'SAR',
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. إنشاء اشتراك للمستأجر
        DB::table('subscriptions')->insert([
            'id' => $subscriptionId,
            'tenant_id' => $tenantId,
            'plan_price_id' => $planPriceId,
            'status' => 'active',
            'starts_at' => now(),
            'current_period_end' => now()->addMonth(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscriptionId,
            'tenant_id' => $tenantId,
            'plan_price_id' => $planPriceId,
            'status' => 'active',
        ]);
    }
}

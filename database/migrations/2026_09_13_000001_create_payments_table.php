<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل ترحيل إنشاء جدول المدفوعات والفواتير للمنظومة.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // ارتباط الفاتورة بالمنشأة والباقة وسعرها
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('plan_price_id')->nullable()->constrained('plan_prices')->nullOnDelete();

            // تفاصيل المبلغ المالي ودورة الاستخدام
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly

            // حالة السداد وبوابة الدفع
            $table->string('status', 20)->default('pending'); // pending, paid, failed, refunded
            $table->string('gateway', 30)->default('stripe');
            $table->string('gateway_reference_id', 100)->nullable()->index();
            $table->string('payment_method', 50)->nullable(); // card, apple_pay, mada...

            // رابط الإيصال وتوقيت السداد الفعلي
            $table->text('receipt_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * التراجع عن الترحيل.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل الترحيل لإنشاء جدول الموردين.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            // 1. عزل المستأجر المتعدد (Multi-Tenancy)
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // 2. التصنيف (ربط اختياري مع تصنيفات الموردين)
            $table->foreignId('vendor_category_id')
                ->nullable()
                ->constrained('vendor_categories')
                ->nullOnDelete();

            // 3. البيانات الأساسية
            $table->string('name');                         // اسم منشأة / شركة المورد
            $table->string('contact_name');                 // اسم مسؤول التواصل
            $table->string('email');                        // البريد الإلكتروني للمورد
            $table->string('phone')->nullable();            // رقم الهاتف

            // 4. حالة المورد
            $table->string('status')->default('pending');   // ['active', 'pending', 'suspended']

            $table->timestamps();

            // القيود والفهارس
            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'vendor_category_id']);
        });
    }

    /**
     * التراجع عن الترحيل.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};

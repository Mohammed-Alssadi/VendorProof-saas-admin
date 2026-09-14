<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل الترحيل لإنشاء جدول تصنيفات الموردين.
     */
    public function up(): void
    {
        Schema::create('vendor_categories', function (Blueprint $table) {
            $table->id();

            // عزل المستأجر التلقائي
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // اسم التصنيف
            $table->string('name');

            // وصف اختياري
            $table->text('description')->nullable();

            // حالة التفعيل (نشط / معطل)
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // قيد لمنع تكرار نفس اسم التصنيف لنفس المنشأة
            $table->unique(['tenant_id', 'name']);
        });
    }

    /**
     * التراجع عن الترحيل.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_categories');
    }
};

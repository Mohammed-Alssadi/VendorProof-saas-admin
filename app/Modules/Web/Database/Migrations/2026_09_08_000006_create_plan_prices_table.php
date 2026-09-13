<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            // منع تكرار نفس دورة الدفع والعملة لنفس الخطة
            $table->unique(['plan_id', 'billing_cycle', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};

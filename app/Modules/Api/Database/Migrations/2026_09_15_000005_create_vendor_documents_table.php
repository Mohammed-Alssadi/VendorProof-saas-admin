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
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignUuid('vendor_requirement_id')->constrained('vendor_requirements')->cascadeOnDelete();
            $table->foreignUuid('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status')->default('pending');
            $table->string('ai_verdict')->nullable();
            $table->text('ai_rejection_reason')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->json('ai_metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'vendor_id', 'status']);
            $table->index(['tenant_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};

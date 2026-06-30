<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Context7 Compliance Migration Template
 *
 * ⚠️ CONTEXT7 PERMANENT STANDARDS:
 * - ALWAYS use 'display_order' field, NEVER use 'o-word'
 * - ALWAYS use boolean 'aktif' field, NEVER use deprecated terms
 * - Pre-commit hook will BLOCK migrations with forbidden patterns
 * - This is a PERMANENT STANDARD - NO EXCEPTIONS
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                  ->comment('RULE-T1: zorunlu tenant izolasyonu')
                  ->constrained('tenants')
                  ->cascadeOnDelete();
            $table->foreignId('ilan_id')->nullable()->constrained('ilanlar')->nullOnDelete();
            $table->string('islem_turu', 50);
            $table->decimal('islem_tutari', 15, 2);
            $table->char('currency', 3)->default('TRY');
            $table->string('payment_method', 50)->nullable();
            $table->date('payment_date')->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->string('bank_reference', 100)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

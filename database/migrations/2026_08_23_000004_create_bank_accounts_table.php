<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * C5.1/C5.3: Bank accounts table for settlement reconciliation
     *
     * Stores bank account metadata for bank transaction ingestion.
     * One row per bank account per tenant.
     *
     * SAAB Phase C5.1 Foundation
     * Baseline: 35b4e6c (C4.2 Certified)
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('bank_name', 100);
            $table->string('account_name', 255);
            $table->string('iban', 34)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('currency', 3)->default('TRY');
            $table->string('account_type', 30)->default('checking'); // checking, savings, corporate
            $table->boolean('is_active')->default(true);
            $table->string('source', 30)->default('manual'); // manual, api, csv, mt940
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'iban'], 'ba_t_iban_unique');
            $table->index(['tenant_id', 'is_active'], 'ba_t_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};

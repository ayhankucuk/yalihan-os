<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase-2: SaaS Monetization Foundation SSOT
 * 
 * Purpose: Establishes the core authority tables for Multi-tenancy, 
 * Subscription Management, and Revenue Enforcement.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 🏗️ SSOT: Tenants
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('domain')->nullable()->unique();
                $table->string('status')->default('active'); // active, suspended, deleted
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 🏗️ SSOT: Plans (Tier definition)
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Free, Basic, Pro, Enterprise
                $table->string('slug')->unique();
                $table->decimal('price', 10, 2)->default(0.00);
                $table->string('currency', 3)->default('USD');
                $table->integer('billing_cycle_days')->default(30);
                $table->json('features')->nullable(); // quota, ai_limit, etc.
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 🏗️ SSOT: Subscriptions (Tenant-Plan bridge)
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('plan_id')->constrained('plans');
                $table->string('status')->default('trialing'); // trialing, active, past_due, canceled
                $table->dateTime('trial_ends_at')->nullable();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at')->nullable();
                $table->dateTime('canceled_at')->nullable();
                $table->timestamps();
            });
        }

        // 🏗️ SSOT: Billing Ledger (Append-only Financial Authority)
        if (!Schema::hasTable('billing_ledger_entries')) {
            Schema::create('billing_ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants');
                $table->string('type'); // plan_fee, usage_fee, credit, refund
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('USD');
                $table->string('reference_type')->nullable(); // subscription, usage_event, invoice
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps(); // Created at acts as immutable ledger time
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_ledger_entries');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('tenants');
    }
};

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
        // 1. Hardening existing financial tables with tenant_id
        Schema::table('ledger_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('ledger_accounts', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants');
            }
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('ledger_entries', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants');
            }
        });

        Schema::table('ledger_balances', function (Blueprint $table) {
            if (!Schema::hasColumn('ledger_balances', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants');
            }
        });

        Schema::table('financial_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_settings', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants');
            }
        });

        // 2. Canonical Naming for SaaS Billing Ledger
        // SQLite uyumluluğu: Her renameColumn ayrı Schema::table bloğunda olmalı.
        if (Schema::hasColumn('billing_ledger_entries', 'amount')) {
            Schema::table('billing_ledger_entries', function (Blueprint $table) {
                $table->renameColumn('amount', 'islem_tutari');
            });
        }
        if (Schema::hasColumn('billing_ledger_entries', 'type')) {
            Schema::table('billing_ledger_entries', function (Blueprint $table) {
                $table->renameColumn('type', 'islem_turu');
            });
        }

        // 3. New Table: AI Credit Balances (Circuit Breaker SSOT)
        if (!Schema::hasTable('ai_credit_balances')) {
            Schema::create('ai_credit_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->unique()->constrained('tenants');
                $table->integer('available_credits')->default(0);
                $table->integer('used_credits')->default(0);
                $table->integer('monthly_limit')->default(0);
                $table->dateTime('last_reset_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_credit_balances');

        // SQLite uyumluluğu: Her renameColumn ayrı Schema::table bloğunda olmalı.
        if (Schema::hasColumn('billing_ledger_entries', 'islem_tutari')) {
            Schema::table('billing_ledger_entries', function (Blueprint $table) {
                $table->renameColumn('islem_tutari', 'amount');
            });
        }
        if (Schema::hasColumn('billing_ledger_entries', 'islem_turu')) {
            Schema::table('billing_ledger_entries', function (Blueprint $table) {
                $table->renameColumn('islem_turu', 'type');
            });
        }

        Schema::table('financial_settings', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('ledger_balances', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddTenantIdToGovernanceDecisions
 * Multi-tenant isolation for cryptographic ledger chain.
 * SAB Core Constitution v2.6
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('governance_decisions', function (Blueprint $table) {
            if (!Schema::hasColumn('governance_decisions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('governance_decisions', function (Blueprint $table) {
            $table->dropColumnIfExists('tenant_id');
        });
    }
};

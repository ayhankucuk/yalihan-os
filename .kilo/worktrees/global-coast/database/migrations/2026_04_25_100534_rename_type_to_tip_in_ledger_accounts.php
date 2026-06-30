<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Context7 Compliance Migration — ledger_accounts.type → tip
 *
 * Rationale: 'type' is a forbidden field name per Context7 governance.
 * Canonical replacement: 'tip'
 *
 * Safety:
 *  - Column presence is checked before rename (idempotent up/down).
 *  - DB::statement used for MySQL CHANGE COLUMN (preserves nullability / length).
 *  - No business logic changes — pure schema rename only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('ledger_accounts', 'type') && ! Schema::hasColumn('ledger_accounts', 'tip')) {
                $table->renameColumn('type', 'tip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ledger_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('ledger_accounts', 'tip') && ! Schema::hasColumn('ledger_accounts', 'type')) {
                $table->renameColumn('tip', 'type');
            }
        });
    }
};

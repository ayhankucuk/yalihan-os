<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zero Trust Forensics — Hash Chain Kolonlarının Eklenmesi
 * SAB Core Constitution v2.6 — Anti-Bypass Guard aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('governance_decisions', function (Blueprint $table) {
            if (!Schema::hasColumn('governance_decisions', 'prev_hash')) {
                $table->string('prev_hash', 64)->nullable()->after('karar_notu');
            }
            if (!Schema::hasColumn('governance_decisions', 'current_hash')) {
                $table->string('current_hash', 64)->nullable()->after('prev_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('governance_decisions', function (Blueprint $table) {
            $table->dropColumnIfExists('prev_hash');
            $table->dropColumnIfExists('current_hash');
        });
    }
};

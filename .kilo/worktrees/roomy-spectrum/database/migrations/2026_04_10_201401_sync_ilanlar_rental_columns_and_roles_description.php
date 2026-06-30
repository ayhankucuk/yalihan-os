<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema Sync Migration — Rental Engine Columns + Roles Description
 *
 * Root Cause: mysql-schema.sql was behind testing-schema.sql for rental engine
 * columns that IlanCrudService::mapCoreData() writes to.
 *
 * Domain Audit (10 Nisan 2026):
 * ✅ VALID: max_stay_nights, base_guest_count, extra_guest_fee, security_deposit,
 *          booking_type, cancellation_policy, price_text — all have domain write+read paths
 * ⚠️ DEPRECATED ALIAS: iptal_politikasi — dual-write sync with cancellation_policy
 *    (kept for backward compat, mutator syncs both — see Ilan::setCancellationPolicyAttribute)
 * ✅ VALID: roles.description — used by UserFactory for role metadata
 *
 * @see app/Services/Ilan/IlanCrudService.php::mapCoreData() — rental write path
 * @see app/Models/Ilan.php L1391-1410 — cancellation_policy ↔ iptal_politikasi accessors
 * @see database/factories/UserFactory.php L58 — roles.description usage
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── ilanlar: Rental Engine columns ──────────────────────────────
        Schema::table('ilanlar', function (Blueprint $table) {
            if (!Schema::hasColumn('ilanlar', 'max_stay_nights')) {
                $table->unsignedInteger('max_stay_nights')->default(30)
                    ->after('min_stay_nights');
            }
            if (!Schema::hasColumn('ilanlar', 'base_guest_count')) {
                $table->integer('base_guest_count')->default(1)
                    ->after('max_stay_nights');
            }
            if (!Schema::hasColumn('ilanlar', 'extra_guest_fee')) {
                $table->decimal('extra_guest_fee', 10, 2)->default(0)
                    ->after('base_guest_count');
            }
            if (!Schema::hasColumn('ilanlar', 'security_deposit')) {
                $table->decimal('security_deposit', 10, 2)->default(0)
                    ->after('extra_guest_fee');
            }
            if (!Schema::hasColumn('ilanlar', 'booking_type')) {
                $table->string('booking_type', 50)->default('instant')
                    ->after('security_deposit');
            }
            if (!Schema::hasColumn('ilanlar', 'cancellation_policy')) {
                $table->string('cancellation_policy', 50)->default('flexible')
                    ->after('booking_type');
            }
            // Deprecated alias — synced by Ilan model mutators
            if (!Schema::hasColumn('ilanlar', 'iptal_politikasi')) {
                $table->string('iptal_politikasi', 50)->default('flexible')
                    ->after('cancellation_policy');
            }
            if (!Schema::hasColumn('ilanlar', 'price_text')) {
                $table->string('price_text', 255)->nullable()
                    ->after('check_out_time');
            }
        });

        // ── roles: description column ───────────────────────────────────
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'description')) {
                $table->string('description', 255)->nullable()
                    ->after('guard_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            $drops = ['max_stay_nights', 'base_guest_count', 'extra_guest_fee',
                'security_deposit', 'booking_type', 'cancellation_policy',
                'iptal_politikasi', 'price_text'];
            foreach ($drops as $col) {
                if (Schema::hasColumn('ilanlar', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};

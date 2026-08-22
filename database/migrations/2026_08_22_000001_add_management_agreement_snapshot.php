<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * C3.1: Management Agreement + Reservation Commission Snapshot
 *
 * Adds to ilanlar:
 *   - management_model: current commercial agreement (FULL_MANAGEMENT, CHECKIN_CHECKOUT, NONE, CUSTOM)
 *   - custom_commission_rate: optional custom rate for CUSTOM model (DECIMAL(5,4) = fraction)
 *
 * Adds to property_reservations:
 *   - management_model_snapshot: immutable snapshot at booking time
 *   - commission_rate_snapshot: immutable snapshot at booking time (DECIMAL(5,4))
 *
 * Backwards-safe:
 *   - Existing ilanlar: DEFAULT 'FULL_MANAGEMENT' (highest default rate)
 *   - Existing reservations: NULL (no snapshot — legacy/no-data marker)
 *     These are intentionally left as NULL to avoid inventing financial data.
 *
 * Decimal convention: DECIMAL(5,4) = fraction, matches country_financial_rules standard.
 * Examples: 0.1500 = 15%, 0.1000 = 10%, 0.0000 = 0%
 *
 * Baseline: 0c6203f
 * SAAB Decision: C3.1 Certification
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── ilanlar: current agreement ────────────────────────────────────────
        if (!Schema::hasColumn('ilanlar', 'management_model')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table
                    ->string('management_model', 30)
                    ->default('FULL_MANAGEMENT')
                    ->after('rental_currency')
                    ->comment('Yalıhan property management model: FULL_MANAGEMENT|CHECKIN_CHECKOUT|NONE|CUSTOM');
            });
        }

        if (!Schema::hasColumn('ilanlar', 'custom_commission_rate')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table
                    ->decimal('custom_commission_rate', 5, 4)
                    ->nullable()
                    ->default(null)
                    ->after('management_model')
                    ->comment('Custom commission rate for CUSTOM model only (fraction, e.g. 0.1200 = 12%)');
            });
        }

        // ── property_reservations: immutable booking-time snapshot ─────────────
        if (!Schema::hasColumn('property_reservations', 'management_model_snapshot')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table
                    ->string('management_model_snapshot', 30)
                    ->nullable()
                    ->default(null)
                    ->after('arrival_notes')
                    ->comment('Management model at booking time — immutable snapshot');
            });
        }

        if (!Schema::hasColumn('property_reservations', 'commission_rate_snapshot')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table
                    ->decimal('commission_rate_snapshot', 5, 4)
                    ->nullable()
                    ->default(null)
                    ->after('management_model_snapshot')
                    ->comment('Effective commission rate at booking time — immutable (fraction, e.g. 0.1500 = 15%)');
            });
        }
    }

    public function down(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            $table->dropColumn(['management_model_snapshot', 'commission_rate_snapshot']);
        });

        Schema::table('ilanlar', function (Blueprint $table) {
            $table->dropColumn(['management_model', 'custom_commission_rate']);
        });
    }
};

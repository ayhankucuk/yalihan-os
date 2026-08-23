<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * C5.1-D01: VCC Status Parity — Booking.com + Channex VCC Fields
     *
     * SAAB Phase C5.1 — VCC Status Extension
     * Authority: C5.1-D01 / VCC Status Parity
     * Baseline: 35b4e6c (C4.2 Certified)
     *
     * Adds VCC-specific fields to provider_settlements.
     * VCC lifecycle is separate from bank transfer lifecycle.
     *
     * Fields added:
     *   vcc_status         — Canonical VCC lifecycle state
     *   vcc_reference      — Booking.com/Channex VCC card reference
     *   vcc_charged_amount — Amount charged against the VCC
     *   vcc_charge_date    — Date of VCC charge
     *   vcc_currency       — VCC charge currency
     */
    public function up(): void
    {
        Schema::table('provider_settlements', function (Blueprint $table) {
            if (!Schema::hasColumn('provider_settlements', 'vcc_status')) {
                $table->string('vcc_status', 30)->nullable()->after('payout_status');
            }
            if (!Schema::hasColumn('provider_settlements', 'vcc_reference')) {
                $table->string('vcc_reference', 255)->nullable()->after('bank_transfer_reference');
            }
            if (!Schema::hasColumn('provider_settlements', 'vcc_charged_amount')) {
                $table->decimal('vcc_charged_amount', 15, 4)->nullable()->after('vcc_reference');
            }
            if (!Schema::hasColumn('provider_settlements', 'vcc_charge_date')) {
                $table->date('vcc_charge_date')->nullable()->after('vcc_charged_amount');
            }
            if (!Schema::hasColumn('provider_settlements', 'vcc_currency')) {
                $table->string('vcc_currency', 3)->nullable()->after('vcc_charge_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('provider_settlements', function (Blueprint $table) {
            if (Schema::hasColumn('provider_settlements', 'vcc_status')) {
                $table->dropColumn('vcc_status');
            }
            if (Schema::hasColumn('provider_settlements', 'vcc_reference')) {
                $table->dropColumn('vcc_reference');
            }
            if (Schema::hasColumn('provider_settlements', 'vcc_charged_amount')) {
                $table->dropColumn('vcc_charged_amount');
            }
            if (Schema::hasColumn('provider_settlements', 'vcc_charge_date')) {
                $table->dropColumn('vcc_charge_date');
            }
            if (Schema::hasColumn('provider_settlements', 'vcc_currency')) {
                $table->dropColumn('vcc_currency');
            }
        });
    }
};

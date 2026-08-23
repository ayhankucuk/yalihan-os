<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * C4.1: Channel Fee Snapshot & Policy Foundation
     *
     * Adds canonical channel fee snapshot fields to property_reservations.
     * OWNER_BORNE model: OTA fee is a cost deducted from gross before
     * Yalihan management fee and owner payable are calculated.
     *
     * Formula (C4.1 Invariant 1):
     *   owner_payable = gross_booking_amount
     *                 - verified_channel_fee_amount
     *                 - yalihan_management_commission
     *
     * C4.1 Invariant 2:
     *   channel_fee UNKNOWN → payout readiness BLOCKED
     *   System does NOT guess or hard-code channel rates.
     *
     * Channel fee source priority:
     *   1. PROVIDER_REPORTED  — payout report from OTA API
     *   2. PROPERTY_CONFIG     — channel configuration on the property
     *   3. EXPLICIT_RULE      — admin-configured channel rule snapshot
     *   4. UNKNOWN            — no reliable source; payout blocked
     *
     * SAAB Decision: C4_POLICY_LOCKED_OWNER_BORNE
     * Baseline: e9b3111
     */
    public function up(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('property_reservations', 'channel_fee_amount')) {
                $table->decimal('channel_fee_amount', 14, 4)->nullable()
                    ->comment('Channel/OTA fee deducted from gross. NULL = unknown/not yet reported.');
            }

            if (! Schema::hasColumn('property_reservations', 'channel_fee_currency')) {
                $table->string('channel_fee_currency', 3)->nullable()
                    ->comment('Currency of channel_fee_amount. NULL = unknown.');
            }

            if (! Schema::hasColumn('property_reservations', 'channel_fee_rate')) {
                $table->decimal('channel_fee_rate', 7, 6)->nullable()
                    ->comment('Channel fee rate as fraction (e.g. 0.1500 = 15%). NULL = unknown.');
            }

            if (! Schema::hasColumn('property_reservations', 'channel_fee_source')) {
                $table->string('channel_fee_source', 30)->nullable()
                    ->comment('Source of channel fee data: PROVIDER_REPORTED|PROPERTY_CONFIG|EXPLICIT_RULE|UNKNOWN');
            }

            if (! Schema::hasColumn('property_reservations', 'channel_fee_bearer')) {
                $table->string('channel_fee_bearer', 20)->nullable()
                    ->comment('Who bears the channel fee: OWNER_BORNE|YALIHAN_BORNE|COMMISSION_SHARE. SAAB default: OWNER_BORNE');
            }

            if (! Schema::hasColumn('property_reservations', 'channel_fee_captured_at')) {
                $table->timestamp('channel_fee_captured_at')->nullable()
                    ->comment('When the channel fee was captured/reported by the OTA.');
            }

            if (! Schema::hasColumn('property_reservations', 'channel_fee_is_verified')) {
                $table->boolean('channel_fee_is_verified')->default(false)
                    ->comment('True when channel fee has been confirmed against provider report (C5 reconciliation).');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            $columns = [
                'channel_fee_amount',
                'channel_fee_currency',
                'channel_fee_rate',
                'channel_fee_source',
                'channel_fee_bearer',
                'channel_fee_captured_at',
                'channel_fee_is_verified',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('property_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

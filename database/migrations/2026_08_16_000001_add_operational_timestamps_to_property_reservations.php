<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CHECKOUT-D1 — Operational lifecycle timestamps
     *
     * Adds checked_in_at, checked_out_at, completed_at to property_reservations.
     * These are immutable facts, NOT state transitions.
     *
     * Migration: single column set — checked_in_at nullable, checked_out_at nullable,
     * completed_at nullable. Default null until the operation occurs.
     *
     * SAAB Decision CHECKOUT-D1: Option B (timestamps + events)
     * Baseline: 88ccfc8
     */
    public function up(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            // Guest confirmed to be physically in the property.
            // Set when Ayhan/staff marks guest as checked-in, or by self-check-in.
            $table->timestamp('checked_in_at')
                ->nullable()
                ->after('confirmed_at');

            // Guest has departed the property.
            // Set when Ayhan/staff marks guest as checked-out.
            $table->timestamp('checked_out_at')
                ->nullable()
                ->after('checked_in_at');

            // All post-checkout processing is complete (turnover cleaning finished, inspection done).
            // Set by reservation:complete command when end_date has passed.
            $table->timestamp('completed_at')
                ->nullable()
                ->after('checked_out_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checked_out_at', 'completed_at']);
        });
    }
};

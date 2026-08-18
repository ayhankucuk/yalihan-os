<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CHECKIN_CHECKOUT Wave 2 — Guest Arrival Readiness
 *
 * Migration: Adds check-in window and guest arrival fields to property_reservations.
 *
 * INV-W2-I4: checkin_window_opened_at is set at most once (idempotent — only NULL → timestamp)
 * INV-W2-V2: Check-in window opens 24h before start_date
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            // Check-in window: set when 24h before start_date trigger fires.
            // Idempotent: only set if NULL (enforced at service layer)
            if (!Schema::hasColumn('property_reservations', 'checkin_window_opened_at')) {
                $table->timestamp('checkin_window_opened_at')
                    ->nullable()
                    ->after('completed_at');
            }

            // Guest estimated arrival time (e.g. "18:00", "14:00-16:00")
            if (!Schema::hasColumn('property_reservations', 'arrival_time_estimated')) {
                $table->string('arrival_time_estimated', 50)
                    ->nullable()
                    ->after('checkin_window_opened_at');
            }

            // Late arrival notes, special access instructions, etc.
            if (!Schema::hasColumn('property_reservations', 'arrival_notes')) {
                $table->text('arrival_notes')
                    ->nullable()
                    ->after('arrival_time_estimated');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            $columns = ['checkin_window_opened_at', 'arrival_time_estimated', 'arrival_notes'];
            $existing = array_filter($columns, fn($c) => Schema::hasColumn('property_reservations', $c));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};

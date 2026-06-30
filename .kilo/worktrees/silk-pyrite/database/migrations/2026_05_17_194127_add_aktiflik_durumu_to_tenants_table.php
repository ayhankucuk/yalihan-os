<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 2 - Görev 5: Governance İyileştirmeleri
     * Context7 Standardization: Add 'aktiflik_durumu' column to tenants table
     * Replaces English 'status' with Turkish 'aktiflik_durumu'
     *
     * DEFENSIVE MIGRATION: Safe for test environments (SQLite/MySQL)
     */
    public function up(): void
    {
        // Guard: Only proceed if tenants table exists
        if (!Schema::hasTable('tenants')) {
            Log::info('Migration skipped: tenants table does not exist (likely test environment)');
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            // Guard: Only add column if it doesn't exist (idempotent)
            if (!Schema::hasColumn('tenants', 'aktiflik_durumu')) {
                $table->string('aktiflik_durumu')->default('active')->after('domain');
            }
        });

        // Data synchronization: Copy status → aktiflik_durumu
        // Only if both columns exist
        if (Schema::hasColumn('tenants', 'status') && Schema::hasColumn('tenants', 'aktiflik_durumu')) {
            try {
                // Use Laravel's query builder (safer for SQLite)
                DB::table('tenants')
                    ->whereNotNull('status')
                    ->update(['aktiflik_durumu' => DB::raw('status')]);

                Log::info('Migration: Successfully copied status → aktiflik_durumu');
            } catch (\Exception $e) {
                // Non-fatal: Log and continue (test environments may not need this)
                Log::warning('Migration data copy failed (likely SQLite test env): ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Guard: Only proceed if table and column exist
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'aktiflik_durumu')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('aktiflik_durumu');
            });
        }
    }
};

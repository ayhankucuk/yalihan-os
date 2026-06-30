<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TASK-1: Harden Outbound Notifications Schema (Context7 Compliance)
 * 
 * ⚠️ CONTEXT7 STANDARDS:
 * - Turkish field names for system metadata
 * - Atomic column renames using Schema builder
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('outbound_notifications')) {
            return;
        }

        // Block 1: Renames (Split for SQLite compatibility)
        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->renameColumn('retry_count', 'deneme_sayisi');
        });
        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->renameColumn('error_message', 'hata_mesaji');
        });
        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->renameColumn('sent_at', 'gonderim_tarihi');
        });
        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->renameColumn('last_attempt_at', 'son_deneme_tarihi');
        });

        // Block 2: New Columns (After renames to ensure correct placement)
        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->timestamp('basarisiz_olma_tarihi')->nullable()->after('son_deneme_tarihi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('outbound_notifications')) {
            return;
        }

        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->dropColumn('basarisiz_olma_tarihi');
        });
        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->renameColumn('deneme_sayisi', 'retry_count');
        });
        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->renameColumn('hata_mesaji', 'error_message');
        });
        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->renameColumn('gonderim_tarihi', 'sent_at');
        });
        Schema::table('outbound_notifications', function (Blueprint $table) {
            $table->renameColumn('son_deneme_tarihi', 'last_attempt_at');
        });
    }
};

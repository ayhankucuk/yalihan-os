<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Context7: Safe Migration `error_message` → `hata_mesaji` in ai_logs table.
 * Resolves [WFC-013] via Add + Sync mechanism.
 * 
 * Strategy: Add + Sync + Deprecate
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new column (if not exists)
        if (!Schema::hasColumn('ai_logs', 'hata_mesaji')) {
            Schema::table('ai_logs', function (Blueprint $table) {
                $table->text('hata_mesaji')->nullable()->after('calisma_durumu');
            });
        }

        // 2. Sync data (Data migration)
        if (Schema::hasColumn('ai_logs', 'error_message')) {
            DB::table('ai_logs')
                ->whereNull('hata_mesaji')
                ->update(['hata_mesaji' => DB::raw('error_message')]);
            
            // 3. Make old column nullable/deprecated
            Schema::table('ai_logs', function (Blueprint $table) {
                $table->text('error_message')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('ai_logs', function (Blueprint $table) {
            $table->dropColumn('hata_mesaji');
        });
    }
};

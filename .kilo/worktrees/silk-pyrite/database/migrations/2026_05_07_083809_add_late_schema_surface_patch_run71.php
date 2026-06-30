<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Late Schema Surface Patch - Run #71
 *
 * Context: FK Stabilization Phase tamamlandı, ancak 3 geç tespit edilen kolon eksikliği bulundu.
 * Scope: Minimal patch - sadece eksik kolonlar ekleniyor.
 *
 * Eklenen Kolonlar:
 * 1. advisor_photos.kisi_id (FK → kisiler.id)
 * 2. copilot_action_logs.rejection_reason (text, nullable)
 * 3. talepler.tip (string, nullable)
 *
 * Risk: MEDIUM
 * Governance: Operational Guardrails ACTIVE
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. advisor_photos.kisi_id
        if (Schema::hasTable('advisor_photos') && !Schema::hasColumn('advisor_photos', 'kisi_id')) {
            Schema::table('advisor_photos', function (Blueprint $table) {
                $table->foreignId('kisi_id')->nullable()->after('id')->constrained('kisiler')->nullOnDelete();
                $table->index('kisi_id');
            });
        }

        // 2. copilot_action_logs.rejection_reason
        if (Schema::hasTable('copilot_action_logs') && !Schema::hasColumn('copilot_action_logs', 'rejection_reason')) {
            Schema::table('copilot_action_logs', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('status');
            });
        }

        // 3. talepler.tip
        if (Schema::hasTable('talepler') && !Schema::hasColumn('talepler', 'tip')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->string('tip')->nullable()->after('talep_tipi');
                $table->index('tip');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('advisor_photos', 'kisi_id')) {
            Schema::table('advisor_photos', function (Blueprint $table) {
                $table->dropForeign(['kisi_id']);
                $table->dropIndex(['kisi_id']);
                $table->dropColumn('kisi_id');
            });
        }

        if (Schema::hasColumn('copilot_action_logs', 'rejection_reason')) {
            Schema::table('copilot_action_logs', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }

        if (Schema::hasColumn('talepler', 'tip')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->dropIndex(['tip']);
                $table->dropColumn('tip');
            });
        }
    }
};

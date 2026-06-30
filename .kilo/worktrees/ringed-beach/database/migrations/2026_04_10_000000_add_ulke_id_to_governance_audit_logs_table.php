<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🛡️ SAB SEALED
 * Add Country Isolation (Consistency Check with Context7)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('governance_audit_logs', function (Blueprint $table) {
            // ✅ Country Isolation: Every model extending BaseModel must have this.
            $table->unsignedBigInteger('ulke_id')->nullable()->after('actor_id')->index();
            $table->foreign('ulke_id')->references('id')->on('ulkeler')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('governance_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['ulke_id']);
            $table->dropColumn('ulke_id');
        });
    }
};

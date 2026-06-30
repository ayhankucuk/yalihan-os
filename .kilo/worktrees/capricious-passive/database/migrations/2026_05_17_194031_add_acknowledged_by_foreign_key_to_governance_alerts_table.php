<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 2 - Görev 5: Governance İyileştirmeleri
     * Foreign Key constraint for governance_alerts.acknowledged_by → users.id
     */
    public function up(): void
    {
        Schema::table('governance_alerts', function (Blueprint $table) {
            // Add foreign key constraint
            $table->foreign('acknowledged_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('governance_alerts', function (Blueprint $table) {
            $table->dropForeign(['acknowledged_by']);
        });
    }
};

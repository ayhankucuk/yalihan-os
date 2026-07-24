<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CD-005: Database-level uniqueness for Hermes Event Logs and Timeline Projections.
     * Prevents duplicate timeline projection records on concurrent executions or event replays.
     */
    public function up(): void
    {
        Schema::table('hermes_event_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('hermes_event_logs', 'projection_type')) {
                $table->string('projection_type', 100)->nullable()->after('event_class')->index();
            }

            if (!Schema::hasColumn('hermes_event_logs', 'source_event_id')) {
                $table->string('source_event_id', 100)->nullable()->after('projection_type')->index();
            }

            $table->unique(
                ['tenant_id', 'projection_type', 'source_event_id'],
                'hermes_logs_tenant_projection_source_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hermes_event_logs', function (Blueprint $table) {
            $table->dropUnique('hermes_logs_tenant_projection_source_unique');
            $table->dropColumn(['projection_type', 'source_event_id']);
        });
    }
};

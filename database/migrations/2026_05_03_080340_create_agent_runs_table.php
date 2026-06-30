<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Context7 Compliance Migration Template
 *
 * ⚠️ CONTEXT7 PERMANENT STANDARDS:
 * - ALWAYS use 'display_order' field, NEVER use 'o-word'
 * - ALWAYS use boolean 'aktif' field, NEVER use deprecated terms
 * - Pre-commit hook will BLOCK migrations with forbidden patterns
 * - This is a PERMANENT STANDARD - NO EXCEPTIONS
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('agent_runs')) {
            Schema::create('agent_runs', function (Blueprint $table) {
                $table->id();
                $table->string('agent_name');
                $table->string('agent_durumu')->default('running'); // running, completed, failed
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->json('input_summary')->nullable();
                $table->json('output_summary')->nullable();
                $table->json('meta')->nullable();
                $table->unsignedInteger('findings_count')->default(0);
                $table->unsignedInteger('decisions_count')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_runs');
    }
};

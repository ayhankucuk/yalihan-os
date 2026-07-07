<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 4.3 — AI Workforce Vertical Slice
     *
     * Records each agent execution within the AI workforce chain.
     * Enables dashboard metrics, replay, and audit trail.
     */
    public function up(): void
    {
        Schema::create('workforce_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hermes_event_log_id')->nullable()->index();
            $table->unsignedBigInteger('ilan_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('chain_id', 36)->index(); // UUID
            $table->string('agent_name', 50)->index(); // portfolio_agent, photo_agent, etc.
            $table->string('agent_class', 255);
            $table->string('event_received', 100);
            $table->unsignedTinyInteger('event_chain_step')->default(0);
            $table->json('input_payload')->nullable();
            $table->json('output_payload')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->float('duration_ms')->nullable();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['chain_id', 'event_chain_step']);
            $table->index(['ilan_id', 'tenant_id']);
            $table->index(['agent_name', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workforce_execution_logs');
    }
};

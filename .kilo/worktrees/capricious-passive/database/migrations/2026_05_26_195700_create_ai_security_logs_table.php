<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SAB Phase 14 Sprint 1: AI Security Forensic Logs Table
     * Creates tamper-proof audit trail table with hash chain integrity.
     */
    public function up(): void
    {
        Schema::create('ai_security_logs', function (Blueprint $table) {
            $table->id();

            // Event identification
            $table->string('event_type', 50)->index()->comment('prompt_injection, sql_injection, spam_detected, high_anomaly_score, etc.');
            $table->unsignedBigInteger('user_id')->index()->comment('User who triggered the security event');

            // Event context (JSON)
            $table->json('context')->comment('Event-specific data (input, reason, pattern, etc.)');

            // Hash chain for tamper detection
            $table->string('previous_hash', 64)->nullable()->comment('SHA-256 hash of previous log entry (null for first entry)');
            $table->string('current_hash', 64)->unique()->comment('SHA-256 hash of this entry (id + event_type + user_id + context + previous_hash + created_at)');

            $table->timestamps();

            // Composite index for efficient querying
            $table->index(['user_id', 'event_type', 'created_at'], 'idx_user_event_time');

            // Foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_security_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 3.6: Hermes Async Queue Foundation
     * Handler execution tracking table for async handler dispatch
     */
    public function up(): void
    {
        Schema::create('handler_executions', function (Blueprint $table) {
            $table->id();
            $table->string('handler_name');           // e.g., 'TelegramNotificationHandler'
            $table->string('event_name');             // e.g., 'ilan.created'
            $table->string('event_id')->nullable();   // Original event identifier
            $table->json('event_payload');            // Original event data preserved
            $table->string('status')->default('pending'); // pending|dispatched|running|success|failed|dead_letter
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['status', 'handler_name']);
            $table->index(['event_id', 'event_name']);
            $table->index('tenant_id');
        });

        Schema::create('handler_dead_letters', function (Blueprint $table) {
            $table->id();
            $table->string('handler_name');
            $table->string('event_name');
            $table->string('event_id')->nullable();
            $table->json('event_payload');            // Original payload preserved
            $table->unsignedInteger('final_attempt_count');
            $table->text('last_error_message');
            $table->timestamp('failed_at');
            $table->timestamps();

            // Index for retry analysis
            $table->index(['handler_name', 'failed_at']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handler_dead_letters');
        Schema::dropIfExists('handler_executions');
    }
};

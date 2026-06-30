<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Hermes Event Log table for event-driven foundation.
     * Stores all events that flow through Hermes for auditing and replay.
     */
    public function up(): void
    {
        Schema::create('hermes_event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 100)->index();
            $table->string('event_class', 255);
            $table->json('payload');
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->timestamp('occurred_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('status', 20)->default('received')->index();
            $table->json('handler_results')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['event_name', 'tenant_id']);
            $table->index(['status', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hermes_event_logs');
    }
};

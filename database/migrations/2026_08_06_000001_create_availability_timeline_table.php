<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * RESERVATION_CORE Phase 4: Availability Timeline
     *
     * Immutable event log for availability changes.
     * Records cannot be modified or deleted.
     */
    public function up(): void
    {
        if (!Schema::hasTable('availability_timeline')) {
            Schema::create('availability_timeline', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('tenant_id')->index();
                $table->unsignedInteger('property_id')->index();
                $table->date('date');
                $table->string('event_type', 50); // RESERVATION_CONFIRMED, BLOCK_CREATED, etc.
                $table->json('previous_state'); // State before event
                $table->json('new_state');     // State after event
                $table->unsignedBigInteger('reservation_id')->nullable()->index();
                $table->string('source', 50)->default('system'); // reservation, owner, maintenance, external, system
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_type', 50)->default('system'); // user, system, channel
                $table->string('correlation_id', 100)->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                // Composite indexes for common queries
                $table->index(['property_id', 'date']);
                $table->index(['tenant_id', 'property_id', 'date']);
                $table->index(['tenant_id', 'property_id', 'reservation_id']);
                $table->index(['tenant_id', 'property_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_timeline');
    }
};

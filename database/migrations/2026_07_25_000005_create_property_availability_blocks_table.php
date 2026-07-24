<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 18: Range-based availability block table.
     * Block types: RESERVATION, OWNER_BLOCK, MAINTENANCE, CLEANING, OPTION_HOLD, MANUAL_BLOCK.
     */
    public function up(): void
    {
        if (! Schema::hasTable('property_availability_blocks')) {
            Schema::create('property_availability_blocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('property_id');
                $table->unsignedBigInteger('reservation_id')->nullable();
                $table->string('block_type')->default('RESERVATION');
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->string('status')->default('ACTIVE');
                $table->string('source')->default('DIRECT');
                $table->string('idempotency_key', 64)->nullable();
                $table->timestamp('released_at')->nullable();
                $table->timestamps();

                $table->foreign('property_id')->references('id')->on('properties')->onDelete('restrict');
                $table->foreign('reservation_id')->references('id')->on('property_reservations')->onDelete('restrict');
                $table->index(['tenant_id', 'property_id', 'status', 'starts_at', 'ends_at'], 'prop_avail_blocks_range_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_availability_blocks');
    }
};

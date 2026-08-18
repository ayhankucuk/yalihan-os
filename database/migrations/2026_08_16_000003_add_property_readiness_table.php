<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CHECKIN_CHECKOUT Wave 2 — Guest Arrival Readiness
 *
 * Migration: property_readiness table
 * Stores per-reservation readiness aggregate for guest arrival.
 *
 * INV-W2-T1: tenant_id is the isolation root
 * INV-W2-I1: reservation_id is unique (one readiness per reservation)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('property_readiness')) {
            Schema::create('property_readiness', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('reservation_id');
                $table->unsignedBigInteger('ilan_id');

                // Readiness dimensions
                $table->boolean('property_clean')->default(false);
                $table->boolean('access_credential_ready')->default(false);
                $table->boolean('guest_contact_ready')->default(false);
                $table->boolean('amenity_check_complete')->default(false);
                $table->boolean('welcome_kit_prepared')->default(false);

                // Computed aggregate
                $table->boolean('is_ready')->default(false);

                $table->timestamps();

                // Indexes
                $table->index(['tenant_id', 'reservation_id']);
                $table->unique(['reservation_id'], 'property_readiness_reservation_unique');
                $table->index(['tenant_id', 'ilan_id']);

                // Foreign keys
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->onDelete('cascade');

                $table->foreign('reservation_id')
                    ->references('id')
                    ->on('property_reservations')
                    ->onDelete('cascade');

                $table->foreign('ilan_id')
                    ->references('id')
                    ->on('ilanlar')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_readiness');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unified_calendar_projections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('commercial_offering_id')->nullable()->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->unsignedBigInteger('availability_block_id')->nullable()->index();
            
            $table->date('calendar_date')->index();
            $table->string('source_type', 50)->default('RESERVATION')->index();
            $table->string('status', 50)->default('BOOKED')->index();
            
            $table->decimal('nightly_rate', 12, 2)->nullable();
            $table->string('currency', 3)->default('TRY');
            
            $table->boolean('is_checkin_day')->default(false);
            $table->boolean('is_checkout_day')->default(false);
            
            $table->string('guest_name')->nullable();
            $table->string('external_source')->nullable();
            $table->string('source_event_id', 100)->nullable();
            $table->timestamp('last_projected_at')->useCurrent();
            
            $table->timestamps();

            // Composite uniqueness invariant to prevent duplicate calendar day entries
            $table->unique(
                ['tenant_id', 'property_id', 'calendar_date', 'source_type', 'reservation_id'],
                'unic_cal_proj_tenant_prop_date_res_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unified_calendar_projections');
    }
};

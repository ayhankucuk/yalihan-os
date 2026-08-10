<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('property_reservations', 'external_reservation_id')) {
                $table->string('external_reservation_id', 255)->nullable()->after('ilan_id');
            }
            if (!Schema::hasColumn('property_reservations', 'external_channel')) {
                $table->string('external_channel', 50)->nullable()->after('external_reservation_id');
            }
        });

        // Add index for idempotency lookups
        Schema::table('property_reservations', function (Blueprint $table) {
            try {
                $table->index(
                    ['external_reservation_id', 'external_channel', 'tenant_id'],
                    'idx_ext_reservation_channel_tenant'
                );
            } catch (\Exception) {
                // Index may already exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            try { $table->dropIndex('idx_ext_reservation_channel_tenant'); } catch (\Exception) {}
            foreach (['external_reservation_id', 'external_channel'] as $col) {
                if (Schema::hasColumn('property_reservations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

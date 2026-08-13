<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            // PILOT-002 Wave 3 — Override audit trail
            // Links the new reservation to the conflicting reservation it replaced
            $table->unsignedBigInteger('override_of_id')
                ->nullable()
                ->after('external_channel');

            // Records which user authorized the override (for audit trail)
            $table->unsignedBigInteger('override_authorized_by')
                ->nullable()
                ->after('override_of_id');

            // When the override occurred
            $table->timestamp('override_occurred_at')
                ->nullable()
                ->after('override_authorized_by');
        });
    }

    public function down(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            $table->dropColumn([
                'override_of_id',
                'override_authorized_by',
                'override_occurred_at',
            ]);
        });
    }
};

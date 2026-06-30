<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Final column for Run #70
     */
    public function up(): void
    {
        // property_reservations table - add cancelled_at
        if (Schema::hasTable('property_reservations') && !Schema::hasColumn('property_reservations', 'cancelled_at')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->timestamp('cancelled_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('property_reservations', 'cancelled_at')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->dropColumn('cancelled_at');
            });
        }
    }
};

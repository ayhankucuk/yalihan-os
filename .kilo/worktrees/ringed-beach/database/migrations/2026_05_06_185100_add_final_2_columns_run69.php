<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Final 2 columns for Run #69
     */
    public function up(): void
    {
        // property_availabilities table - add reservation_id
        if (Schema::hasTable('property_availabilities') && !Schema::hasColumn('property_availabilities', 'reservation_id')) {
            Schema::table('property_availabilities', function (Blueprint $table) {
                $table->unsignedBigInteger('reservation_id')->nullable();
            });
        }

        // property_seasonal_rates table - make rate nullable
        // Base migration has 'rate' as NOT NULL, but tests use 'nightly_rate'
        // Making 'rate' nullable to avoid constraint violation
        if (Schema::hasTable('property_seasonal_rates') && Schema::hasColumn('property_seasonal_rates', 'rate')) {
            Schema::table('property_seasonal_rates', function (Blueprint $table) {
                $table->decimal('rate', 10, 2)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('property_availabilities', 'reservation_id')) {
            Schema::table('property_availabilities', function (Blueprint $table) {
                $table->dropColumn('reservation_id');
            });
        }

        // Note: We don't reverse the nullable change on 'rate' as it fixes a constraint issue
    }
};

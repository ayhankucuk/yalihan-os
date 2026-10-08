<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * BOSCH-FIELD-DRIFT: Add hardware verification flag for alan_m2 field.
     * Indicates whether the property area (m²) was verified via hardware/device measurement.
     */
    public function up(): void
    {
        Schema::table('ilanlar_read_model', function (Blueprint $table) {
            $table->boolean('alan_m2_verified_by_hardware')
                ->default(false)
                ->nullable()
                ->after('net_alan_m2')
                ->comment('1=verified by hardware measurement, 0=manual entry or unverified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ilanlar_read_model', function (Blueprint $table) {
            $table->dropColumn('alan_m2_verified_by_hardware');
        });
    }
};

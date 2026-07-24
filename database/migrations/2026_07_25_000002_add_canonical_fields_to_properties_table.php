<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 18 Integration Hardening:
     * Additive migration adding canonical fields to properties table safely.
     */
    public function up(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('uuid');
            }
            if (! Schema::hasColumn('properties', 'ada')) {
                $table->string('ada')->nullable()->after('tkgm_id');
            }
            if (! Schema::hasColumn('properties', 'parsel')) {
                $table->string('parsel')->nullable()->after('ada');
            }
            if (! Schema::hasColumn('properties', 'alan_m2')) {
                $table->decimal('alan_m2', 10, 2)->nullable()->after('parsel');
            }
            if (! Schema::hasColumn('properties', 'oda_sayisi')) {
                $table->integer('oda_sayisi')->nullable()->after('alan_m2');
            }
            if (! Schema::hasColumn('properties', 'banyo_sayisi')) {
                $table->integer('banyo_sayisi')->nullable()->after('oda_sayisi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['idempotency_key', 'ada', 'parsel', 'alan_m2', 'oda_sayisi', 'banyo_sayisi']);
        });
    }
};

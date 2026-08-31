<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yazlik Details SoftDeletes Column Alignment
 *
 * Adds deleted_at column to yazlik_details table idempotently to align with
 * production schema (mysql-schema.sql) and YazlikDetail model SoftDeletes trait.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('yazlik_details') && !Schema::hasColumn('yazlik_details', 'deleted_at')) {
            Schema::table('yazlik_details', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('yazlik_details') && Schema::hasColumn('yazlik_details', 'deleted_at')) {
            Schema::table('yazlik_details', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

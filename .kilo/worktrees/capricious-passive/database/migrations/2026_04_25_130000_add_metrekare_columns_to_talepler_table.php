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
        Schema::table('talepler', function (Blueprint $table) {
            if (!Schema::hasColumn('talepler', 'min_metrekare')) {
                $table->integer('min_metrekare')->nullable();
            }
            if (!Schema::hasColumn('talepler', 'max_metrekare')) {
                $table->integer('max_metrekare')->nullable();
            }
            if (!Schema::hasColumn('talepler', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talepler', function (Blueprint $table) {
            $table->dropColumn(['min_metrekare', 'max_metrekare', 'metadata']);
        });
    }
};

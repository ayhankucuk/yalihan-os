<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Final 2 surfaces for Run #63
     */
    public function up(): void
    {
        // Add missing column
        if (Schema::hasTable('property_calendar_feeds') && !Schema::hasColumn('property_calendar_feeds', 'last_sync_error')) {
            Schema::table('property_calendar_feeds', function (Blueprint $table) {
                $table->text('last_sync_error')->nullable();
            });
        }

        // Create missing table
        if (!Schema::hasTable('talep_match_projection')) {
            Schema::create('talep_match_projection', function (Blueprint $table) {
                $table->id();
                $table->integer('city')->nullable();
                $table->integer('district')->nullable();
                $table->string('property_type')->nullable();
                $table->decimal('min_price', 15, 2)->nullable();
                $table->decimal('max_price', 15, 2)->nullable();
                $table->integer('match_count')->default(0);
                $table->timestamps();

                $table->index(['city', 'property_type']);
                $table->index(['min_price', 'max_price']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('property_calendar_feeds', 'last_sync_error')) {
            Schema::table('property_calendar_feeds', function (Blueprint $table) {
                $table->dropColumn('last_sync_error');
            });
        }

        Schema::dropIfExists('talep_match_projection');
    }
};

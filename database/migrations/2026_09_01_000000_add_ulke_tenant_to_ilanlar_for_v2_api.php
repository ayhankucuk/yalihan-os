<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V2 API Test Support Migration
     *
     * V2\Ilan modeli ulke_id ve tenant_id kolonlarına ihtiyaç duyar.
     * Bu migration test ortamında bu kolonları ekler.
     * Production schema zaten bu kolonları içerir.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('ilanlar', 'tenant_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            });
        }

        if (!Schema::hasColumn('ilanlar', 'ulke_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->unsignedBigInteger('ulke_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Test migration — geri alınamaz (production schema korunur)
    }
};

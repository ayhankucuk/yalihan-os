<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SAB Context7 Compliant Migration
     * - aktiflik_durumu (boolean) — canonical for is_active
     * - poi_adi, poi_turu, poi_kategorisi — Context7 Türkçe alan adları
     * - display_order — canonical for sort_order/order
     * - ulke_id — HasCountryScope desteği
     */
    public function up(): void
    {
        if (Schema::hasTable('point_of_interests')) {
            return; // Idempotent
        }

        Schema::create('point_of_interests', function (Blueprint $table) {
            $table->id();
            $table->string('poi_adi');
            $table->string('poi_turu', 100);          // school, hospital, beach, marina vb.
            $table->string('poi_kategorisi', 100);     // education, health, transport vb.
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('rating', 3, 1)->nullable();
            $table->json('ek_veri')->nullable();       // address, phone, url vb.
            $table->boolean('aktiflik_durumu')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->unsignedBigInteger('ulke_id')->nullable()->index();
            $table->timestamps();

            $table->index(['lat', 'lng'], 'idx_poi_coordinates');
            $table->index(['poi_turu', 'aktiflik_durumu'], 'idx_poi_type_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_of_interests');
    }
};

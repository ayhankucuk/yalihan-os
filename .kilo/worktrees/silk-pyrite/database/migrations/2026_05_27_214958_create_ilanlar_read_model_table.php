<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * İlanlar Read Model — CQRS Projection Table
     *
     * Context7 Compliance:
     * - yayin_durumu (not status)
     * - aktiflik_durumu (not is_active)
     * - display_order (not sort_order)
     * - one_cikan (not featured)
     * - kapak_resmi (not featured_image)
     * - lat/lng (not latitude/longitude)
     * - il/il_adi (not city/sehir)
     * - ana_kategori_id (not property_type)
     * - alt_kategori_id (not property_category)
     *
     * Idempotency: son_islenen_sira_numarasi column
     * Tenant Isolation: tenant_id + ulke_id
     * Denormalized: Optimized for read queries
     */
    public function up(): void
    {
        Schema::create('ilanlar_read_model', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Tenant Isolation (Multi-Tenancy)
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('ulke_id')->nullable()->index();

            // Aggregate Reference
            $table->unsignedBigInteger('ilan_id')->unique()->comment('FK to ilanlar.id');

            // Idempotency Protection
            $table->unsignedInteger('son_islenen_sira_numarasi')->default(0)
                ->comment('Last processed event sequence number for idempotency');

            // Core Property Fields (Context7 Canonical)
            $table->string('baslik', 255);
            $table->text('aciklama')->nullable();
            $table->string('yayin_durumu', 50)->index()->comment('draft|published|archived');
            $table->tinyInteger('aktiflik_durumu')->default(1)->index()->comment('1=active, 0=inactive');
            $table->tinyInteger('one_cikan')->default(0)->index()->comment('1=featured, 0=normal');
            $table->string('kapak_resmi', 500)->nullable()->comment('Primary image URL');

            // Category (Context7: ana_kategori_id, alt_kategori_id)
            $table->unsignedBigInteger('ana_kategori_id')->nullable()->index()->comment('FK to kategoriler (main type)');
            $table->unsignedBigInteger('alt_kategori_id')->nullable()->index()->comment('FK to kategoriler (sub category)');

            // Location (Context7: il/il_adi, lat/lng)
            $table->string('il', 100)->nullable()->index()->comment('Province name');
            $table->string('ilce', 100)->nullable()->index()->comment('District name');
            $table->string('mahalle', 150)->nullable()->comment('Neighborhood');
            $table->decimal('lat', 10, 7)->nullable()->index()->comment('Latitude');
            $table->decimal('lng', 10, 7)->nullable()->index()->comment('Longitude');

            // Pricing
            $table->decimal('fiyat', 15, 2)->nullable()->index()->comment('Price in TRY');
            $table->string('doviz_birimi', 10)->default('TRY')->comment('Currency code');

            // Property Specs
            $table->unsignedSmallInteger('oda_sayisi')->nullable()->comment('Number of rooms');
            $table->unsignedSmallInteger('banyo_sayisi')->nullable()->comment('Number of bathrooms');
            $table->unsignedInteger('brut_alan_m2')->nullable()->index()->comment('Gross area in m²');
            $table->unsignedInteger('net_alan_m2')->nullable()->comment('Net area in m²');
            $table->unsignedSmallInteger('bina_yasi')->nullable()->comment('Building age in years');
            $table->unsignedSmallInteger('bulundugu_kat')->nullable()->comment('Floor number');

            // Owner/Agent
            $table->unsignedBigInteger('sahip_id')->nullable()->index()->comment('FK to kisiler (owner)');
            $table->unsignedBigInteger('sorumlu_danisman_id')->nullable()->index()->comment('FK to users (assigned agent)');

            // Display & SEO
            $table->unsignedInteger('display_order')->default(0)->index()->comment('Sort order for listings');
            $table->string('slug', 300)->nullable()->unique()->comment('SEO-friendly URL slug');

            // Engagement Metrics (Denormalized for Performance)
            $table->unsignedInteger('goruntulenme_sayisi')->default(0)->comment('View count');
            $table->unsignedInteger('favori_sayisi')->default(0)->comment('Favorite count');
            $table->unsignedInteger('iletisim_sayisi')->default(0)->comment('Contact count');

            // Timestamps
            $table->timestamp('ilan_olusturulma_tarihi')->nullable()->comment('Original property creation date');
            $table->timestamp('son_guncelleme_tarihi')->nullable()->comment('Last property update date');
            $table->timestamps(); // Projection table own timestamps

            // Foreign Keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('ilan_id')->references('id')->on('ilanlar')->onDelete('cascade');

            // Composite Indexes for Common Queries
            $table->index(['tenant_id', 'yayin_durumu', 'aktiflik_durumu'], 'idx_ilanlar_tenant_status');
            $table->index(['tenant_id', 'il', 'fiyat'], 'idx_tenant_location_price');
            $table->index(['tenant_id', 'ana_kategori_id', 'fiyat'], 'idx_tenant_category_price');
            $table->index(['tenant_id', 'one_cikan', 'display_order'], 'idx_tenant_featured');
            $table->index(['sorumlu_danisman_id', 'yayin_durumu'], 'idx_agent_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ilanlar_read_model');
    }
};

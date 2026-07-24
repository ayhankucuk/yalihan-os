<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SAAB v11 Sprint 17B:
     * Creates commercial_offerings table to decouple commercial terms
     * (Satılık, Kiralık, Sezonluk Kiralama, Fiyat, Para Birimi)
     * from physical Property and marketing Listing.
     */
    public function up(): void
    {
        if (Schema::hasTable('commercial_offerings')) {
            return;
        }

        Schema::create('commercial_offerings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('workspace_id')->comment('Link to PropertyWorkspace');
            $table->unsignedBigInteger('property_id')->comment('Link to Property aggregate');
            $table->string('offering_type', 32)->comment('SATILIK | KIRALIK | SEZONLUK_KIRALIK');
            $table->decimal('fiyat', 15, 2)->comment('Decimal precision price, no floating-point money');
            $table->string('para_birimi', 3)->default('TRY')->comment('TRY | EUR | USD | GBP');
            $table->decimal('komisyon_orani', 5, 2)->nullable();
            $table->decimal('depozito', 15, 2)->nullable();
            $table->string('yayin_durumu', 20)->default('DRAFT')->comment('DRAFT | ACTIVE | SUSPENDED | TERMINATED');
            $table->date('baslangic_tarihi')->nullable();
            $table->date('bitis_tarihi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');

            // Indexes
            $table->index(['tenant_id', 'offering_type'], 'comm_offering_tenant_type_idx');
            $table->index(['tenant_id', 'workspace_id'], 'comm_offering_tenant_ws_idx');
            $table->index(['property_id', 'yayin_durumu'], 'comm_offering_prop_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_offerings');
    }
};

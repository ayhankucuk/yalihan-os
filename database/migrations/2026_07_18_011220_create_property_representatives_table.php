<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_representatives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('kisi_id');
            $table->enum('temsil_yetu_tipi', ['FULL', 'FINANCIAL', 'OPERATIONAL', 'LEGAL'])
                ->comment('Representative authority type');
            $table->date('baslangic_tarihi');
            $table->date('bitis_tarihi')->nullable();
            $table->text('notu')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('restrict');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('restrict');
            $table->foreign('kisi_id')->references('id')->on('kisiler')->onDelete('restrict');

            $table->index(['property_id', 'bitis_tarihi'], 'idx_pr_property_active');
            $table->index(['tenant_id'], 'idx_pr_tenant');
            $table->index(['property_id', 'temsil_yetu_tipi', 'bitis_tarihi'], 'idx_pr_type_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_representatives');
    }
};

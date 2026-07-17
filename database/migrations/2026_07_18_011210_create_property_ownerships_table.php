<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_ownerships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('kisi_id');
            $table->decimal('pay_orani', 6, 4)->default(1.0000)
                ->comment('Ownership share: 0.0001–1.0000');
            $table->enum('sahiplik_tipi', ['OWNER', 'BENEFICIAL_OWNER', 'JOINT_OWNER', 'REPRESENTATIVE'])
                ->default('OWNER');
            $table->unsignedBigInteger('yetkili_temsilci_id')->nullable();
            $table->date('baslangic_tarihi');
            $table->date('bitis_tarihi')->nullable()
                ->comment('null = currently active');
            $table->enum('atama_kaynagi', ['MANUAL', 'CONTRACT', 'INHERITANCE', 'COURT', 'TKGM'])
                ->default('MANUAL');
            $table->text('atama_notu')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('restrict');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('restrict');
            $table->foreign('kisi_id')->references('id')->on('kisiler')->onDelete('restrict');
            $table->foreign('yetkili_temsilci_id')->references('id')->on('kisiler')->onDelete('set null');

            $table->index(['property_id', 'bitis_tarihi'], 'idx_po_property_active');
            $table->index(['property_id', 'baslangic_tarihi', 'bitis_tarihi'], 'idx_po_property_historical');
            $table->index(['tenant_id'], 'idx_po_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_ownerships');
    }
};

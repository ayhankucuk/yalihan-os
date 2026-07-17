<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id');
            $table->enum('dokuman_tipi', [
                'TITLE_DEED',
                'MANAGEMENT_AGREEMENT',
                'OWNER_AUTHORIZATION',
                'ID_DOCUMENT',
                'COMPANY_DOCUMENT',
                'INSURANCE',
                'OCCUPANCY_PERMIT',
                'ZONING',
                'UTILITY_SUBSCRIPTION',
                'KEY_RECEIPT',
            ])->comment('Document classification');
            $table->string('dosya_yolu', 500)->nullable()
                ->comment('Points to existing media storage — no duplicate storage');
            $table->string('referans_no', 255)->nullable()
                ->comment('Title deed number, policy number, etc.');
            $table->date('yayin_tarihi')->nullable()->comment('Issue date');
            $table->date('son_gecerlilik_tarihi')->nullable()->comment('Expiry date');
            $table->enum('durum', ['AKTIF', 'SURESI_DOLMUS', 'IPTAL'])->default('AKTIF');
            $table->text('notu')->nullable();
            $table->unsignedBigInteger('olusturan_id');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('restrict');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('restrict');
            $table->foreign('olusturan_id')->references('id')->on('users')->onDelete('restrict');

            $table->index(['property_id', 'durum'], 'idx_pd_property_status');
            $table->index(['tenant_id'], 'idx_pd_tenant');
            $table->index(['son_gecerlilik_tarihi', 'durum'], 'idx_pd_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_documents');
    }
};

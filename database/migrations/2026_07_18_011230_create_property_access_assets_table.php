<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_access_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id');
            $table->enum('varlik_tipi', ['KEY', 'SITE_CARD', 'GARAGE_REMOTE', 'SMART_LOCK', 'ALARM_CODE', 'STORAGE_KEY'])
                ->comment('Type of access asset');
            $table->string('tanimlayici_no', 255)->nullable()
                ->comment('Key number, card UID — SENSITIVE, hidden by default');
            $table->string('tanim')->nullable()->comment('Human-readable description');
            $table->enum('durum', ['AKTIF', 'KAYIP', 'DEAKTIVE', 'IPTAL'])->default('AKTIF');
            $table->unsignedBigInteger('olusturan_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('restrict');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('restrict');
            $table->foreign('olusturan_id')->references('id')->on('users')->onDelete('restrict');

            $table->index(['property_id', 'durum'], 'idx_paa_property_status');
            $table->index(['tenant_id'], 'idx_paa_tenant');
        });

        Schema::create('property_key_custodies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('kisi_id')->comment('Current holder');
            $table->enum('islem_tipi', ['TESLIM', 'IADE', 'KAYIP_BILDIRIM', 'YENILEME'])->notDefault()
                ->comment('TESLIM=handover, IADE=return, KAYIP=lost report, YENILEME=replacement');
            $table->timestamp('islem_tarihi')->useCurrent();
            $table->text('notu')->nullable();
            $table->unsignedBigInteger('olusturan_id');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('restrict');
            $table->foreign('asset_id')->references('id')->on('property_access_assets')->onDelete('restrict');
            $table->foreign('kisi_id')->references('id')->on('kisiler')->onDelete('restrict');
            $table->foreign('olusturan_id')->references('id')->on('users')->onDelete('restrict');

            $table->index(['asset_id', 'islem_tipi', 'islem_tarihi'], 'idx_pkc_asset_history');
            $table->index(['tenant_id'], 'idx_pkc_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_key_custodies');
        Schema::dropIfExists('property_access_assets');
    }
};

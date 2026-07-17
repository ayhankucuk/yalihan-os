<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Create new table with nullable kisi_id
        Schema::create('property_key_custodies_v2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('kisi_id')->nullable()
                ->comment('Nullable: IADE records have no holder');
            $table->enum('islem_tipi', ['TESLIM', 'IADE', 'KAYIP_BILDIRIM', 'YENILEME']);
            $table->timestamp('islem_tarihi')->useCurrent();
            $table->text('notu')->nullable();
            $table->unsignedBigInteger('olusturan_id');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('restrict');
            $table->foreign('asset_id')->references('id')->on('property_access_assets')->onDelete('restrict');
            $table->foreign('kisi_id')->references('id')->on('kisiler')->onDelete('set null');
            $table->foreign('olusturan_id')->references('id')->on('users')->onDelete('restrict');

            $table->index(['asset_id', 'islem_tipi', 'islem_tarihi'], 'idx_pkc_asset_history_v2');
            $table->index(['tenant_id'], 'idx_pkc_tenant_v2');
        });

        // Step 2: Copy data if old table has data (only existing columns)
        if (Schema::hasTable('property_key_custodies')) {
            $columns = 'id, tenant_id, asset_id, kisi_id, islem_tipi, islem_tarihi, notu, olusturan_id, idempotency_key, created_at, updated_at';
            DB::statement("INSERT INTO property_key_custodies_v2 ({$columns}) SELECT {$columns} FROM property_key_custodies");
        }

        // Step 3: Drop old table and rename new
        Schema::dropIfExists('property_key_custodies');
        Schema::rename('property_key_custodies_v2', 'property_key_custodies');
    }

    public function down(): void
    {
        Schema::create('property_key_custodies_v2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('kisi_id')
                ->comment('NOT nullable in rollback');
            $table->enum('islem_tipi', ['TESLIM', 'IADE', 'KAYIP_BILDIRIM', 'YENILEME']);
            $table->timestamp('islem_tarihi')->useCurrent();
            $table->text('notu')->nullable();
            $table->unsignedBigInteger('olusturan_id');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_id', 'islem_tipi', 'islem_tarihi'], 'idx_pkc_asset_history_v2');
            $table->index(['tenant_id'], 'idx_pkc_tenant_v2');
        });

        if (Schema::hasTable('property_key_custodies')) {
            $columns = 'id, tenant_id, asset_id, kisi_id, islem_tipi, islem_tarihi, notu, olusturan_id, idempotency_key, created_at, updated_at';
            DB::statement("INSERT INTO property_key_custodies_v2 ({$columns}) SELECT {$columns} FROM property_key_custodies WHERE kisi_id IS NOT NULL");
        }

        Schema::dropIfExists('property_key_custodies');
        Schema::rename('property_key_custodies_v2', 'property_key_custodies');
    }
};

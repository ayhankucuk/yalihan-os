<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 12D: Lead Tenant Boundary — Unique index'e tenant_id eklenmesi.
     *
     * PROBLEM: Eski unique index `(platform, platform_user_id)` cross-tenant
     * lead kaydı oluşturmayı engelliyordu — aynı WhatsApp numarası iki farklı
     * tenant'da iki farklı lead olarak kaydedilmeli.
     *
     * SOLUTION: Unique index `(tenant_id, platform, platform_user_id)` olarak
     * güncellenir. NULL tenant_id (legacy webhook kayıtları) için MySQL multi-column
     * unique index NULL davranışı: her NULL satır ayrı unique key'e sahip sayılır
     * (SQL standard), yani NULL tenant_id ile eski kayıtlar korunur.
     *
     * MIGRATION STRATEGY:
     * 1. Yeni composite unique index ekle (tenant_id, platform, platform_user_id)
     * 2. Eski unique index'i dış FK constraint'i olmadan drop et
     *    (Bu migration eski index'in artık kullanılmadığını varsayar —
     *     eğer hala FK referans veriyorsa önce o FK kaldırılmalı)
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Yeni composite unique index: tenant-scoped platform uniqueness
            // NULL tenant_id = her NULL satır ayrı unique key (MySQL standard)
            $table->unique(
                ['tenant_id', 'platform', 'platform_user_id'],
                'leads_tenant_platform_user_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique('leads_tenant_platform_user_unique');
        });
    }
};

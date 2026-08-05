<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CERT-DEBT-001 Fix: yazlik_details tablosuna SoftDeletes desteği ekle
 *
 * Problem: YazlikDetail modeli SoftDeletes trait kullanıyor ama migration
 * deleted_at sütunu oluşturmuyordu. Bu nedenle OwnerIlanController store()
 * ve update() 500 hatası veriyordu.
 *
 * Coverage:
 * - OwnerIlanCrudTest::owner_can_store_new_ilan_as_taslak (CDT-001)
 * - OwnerIlanCrudTest::store_always_assigns_authenticated_user_as_owner (CDT-002)
 * - OwnerIlanCrudTest::owner_can_update_own_ilan (CDT-003)
 */
return new class extends Migration
{
    public function up(): void
    {
        // SoftDeletes için deleted_at sütunu ekle (nullable timestamp)
        if (!Schema::hasColumn('yazlik_details', 'deleted_at')) {
            Schema::table('yazlik_details', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('yazlik_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

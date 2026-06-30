<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add parent_version_hash to property_config_versions
        if (Schema::hasTable('property_config_versions') && !Schema::hasColumn('property_config_versions', 'parent_version_hash')) {
            Schema::table('property_config_versions', function (Blueprint $table) {
                $table->string('parent_version_hash', 64)->nullable()->after('version_hash');
                $table->index('parent_version_hash');
            });
        }

        // Add guest_email to property_reservations
        if (Schema::hasTable('property_reservations') && !Schema::hasColumn('property_reservations', 'guest_email')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->string('guest_email')->nullable()->after('guest_phone');
            });
        }

        // Add quality_score to ilanlar
        if (Schema::hasTable('ilanlar') && !Schema::hasColumn('ilanlar', 'quality_score')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->decimal('quality_score', 5, 2)->nullable()->after('durum');
                $table->index('quality_score');
            });
        }

        // Add aktiflik_durumu to alt_kategori_yayin_tipi
        if (Schema::hasTable('alt_kategori_yayin_tipi') && !Schema::hasColumn('alt_kategori_yayin_tipi', 'aktiflik_durumu')) {
            Schema::table('alt_kategori_yayin_tipi', function (Blueprint $table) {
                $table->boolean('aktiflik_durumu')->default(true)->after('yayin_tipi_id');
                $table->index('aktiflik_durumu');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('property_config_versions', 'parent_version_hash')) {
            Schema::table('property_config_versions', function (Blueprint $table) {
                $table->dropIndex(['parent_version_hash']);
                $table->dropColumn('parent_version_hash');
            });
        }

        if (Schema::hasColumn('property_reservations', 'guest_email')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->dropColumn('guest_email');
            });
        }

        if (Schema::hasColumn('ilanlar', 'quality_score')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropIndex(['quality_score']);
                $table->dropColumn('quality_score');
            });
        }

        if (Schema::hasColumn('alt_kategori_yayin_tipi', 'aktiflik_durumu')) {
            Schema::table('alt_kategori_yayin_tipi', function (Blueprint $table) {
                $table->dropIndex(['aktiflik_durumu']);
                $table->dropColumn('aktiflik_durumu');
            });
        }
    }
};

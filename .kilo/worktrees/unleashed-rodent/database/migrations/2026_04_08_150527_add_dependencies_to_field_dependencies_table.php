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
        if (!Schema::hasTable('kategori_yayin_tipi_field_dependencies')) {
            return;
        }

        Schema::table('kategori_yayin_tipi_field_dependencies', function (Blueprint $table) {
            $table->json('dependencies')->nullable()->after('field_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('kategori_yayin_tipi_field_dependencies')) {
            return;
        }

        Schema::table('kategori_yayin_tipi_field_dependencies', function (Blueprint $table) {
            $table->dropColumn('dependencies');
        });
    }
};

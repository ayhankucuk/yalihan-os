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
        if (!Schema::hasTable('emlak_projeleri')) {
            Schema::create('emlak_projeleri', function (Blueprint $table) {
                $table->id();
                $table->string('gelistirici_adi')->nullable();
                $table->date('tamamlanma_tarihi')->nullable();
                $table->string('yayin_durumu')->default('taslak');
                $table->boolean('one_cikan')->default(false);
                $table->string('adres_il')->nullable();
                $table->string('adres_ilce')->nullable();
                $table->string('adres_mahalle')->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('emlak_proje_translations')) {
            Schema::create('emlak_proje_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proje_id')->constrained('emlak_projeleri')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('proje_adi');
                $table->text('aciklama')->nullable();
                $table->timestamps();

                $table->unique(['proje_id', 'locale']);
            });
        }

        if (!Schema::hasTable('emlak_proje_gorselleri')) {
            Schema::create('emlak_proje_gorselleri', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proje_id')->constrained('emlak_projeleri')->cascadeOnDelete();
                $table->string('dosya_yolu');
                $table->integer('sira')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emlak_proje_gorselleri');
        Schema::dropIfExists('emlak_proje_translations');
        Schema::dropIfExists('emlak_projeleri');
    }
};

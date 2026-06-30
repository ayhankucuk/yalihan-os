<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tkgm_queries')) {
        Schema::create('tkgm_queries', function (Blueprint $table) {
            $table->id();
            $table->string('ada')->nullable();
            $table->string('parsel')->nullable();
            $table->unsignedBigInteger('il_id')->nullable()->index();
            $table->unsignedBigInteger('ilce_id')->nullable()->index();
            $table->unsignedBigInteger('mahalle_id')->nullable()->index();
            $table->decimal('alan_m2', 12, 2)->nullable();
            $table->decimal('kaks', 5, 2)->nullable();
            $table->unsignedInteger('taks')->nullable();
            $table->string('nitelik')->nullable();
            $table->unsignedInteger('gabari')->nullable();
            $table->unsignedBigInteger('ilan_id')->nullable()->index();
            $table->decimal('satis_fiyati', 15, 2)->nullable();
            $table->date('satis_tarihi')->nullable();
            $table->unsignedInteger('satis_suresi_gun')->nullable();
            $table->string('query_source')->nullable()->index(); // api, manual, batch
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('queried_at')->nullable()->index();
            $table->json('tkgm_raw_data')->nullable();
            $table->boolean('islem_durumu')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ada', 'parsel', 'il_id']);
            $table->index(['il_id', 'ilce_id', 'mahalle_id']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tkgm_queries');
    }
};

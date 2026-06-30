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
        if (!Schema::hasTable('lead_embeddings')) {
            Schema::create('lead_embeddings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->unsignedBigInteger('kisi_id')->nullable();
                $table->longText('embedding'); // JSON array
                $table->string('model_name')->default('nomic-embed-text');
                $table->integer('dimensions')->default(768);
                $table->tinyInteger('aktiflik_durumu')->default(1);
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->index('lead_id');
                $table->index('kisi_id');
                $table->index('model_name');
            });
        }

        if (!Schema::hasTable('ilan_embeddings')) {
            Schema::create('ilan_embeddings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id');
                $table->longText('embedding'); // JSON array
                $table->string('model_name')->default('nomic-embed-text');
                $table->integer('dimensions')->default(768);
                $table->tinyInteger('aktiflik_durumu')->default(1);
                $table->timestamps();

                $table->index('ilan_id');
                $table->index('model_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ilan_embeddings');
        Schema::dropIfExists('lead_embeddings');
    }
};

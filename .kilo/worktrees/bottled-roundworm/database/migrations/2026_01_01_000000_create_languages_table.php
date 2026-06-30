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
        if (!Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ulke_id')->nullable();
                $table->string('code', 10);
                $table->string('name', 100);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->boolean('varsayilan_durumu')->default(false);
                $table->boolean('is_rtl')->default(false);
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};

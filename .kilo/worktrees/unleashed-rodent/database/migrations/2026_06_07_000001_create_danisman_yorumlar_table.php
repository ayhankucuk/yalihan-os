<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('danisman_yorumlar')) {
            return;
        }

        Schema::create('danisman_yorumlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('danisman_id')->constrained('users')->cascadeOnDelete();
            $table->string('musteri_adi')->nullable();
            $table->text('yorum')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('onay_durumu', 50)->default('pending'); // pending | approved | rejected
            $table->timestamps();
            $table->softDeletes();

            $table->index('danisman_id');
            $table->index('onay_durumu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danisman_yorumlar');
    }
};

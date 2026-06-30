<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();
            $table->unique('name');
        });

        Schema::create('crm_kisi_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('kisi_id');
            $table->unsignedBigInteger('tag_id');
            $table->primary(['kisi_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_kisi_tag');
        Schema::dropIfExists('crm_tags');
    }
};

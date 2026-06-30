<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kisi_id')->nullable();
            $table->string('type');
            $table->text('note')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->index(['kisi_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};

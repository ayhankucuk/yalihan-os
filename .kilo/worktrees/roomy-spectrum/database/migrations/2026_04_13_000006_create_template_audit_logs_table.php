<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulke_id')->nullable()->index();
            $table->string('auditable_type')->index();
            $table->unsignedBigInteger('auditable_id')->index();
            $table->string('event')->index(); // created, updated, deleted, restored
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_audit_logs');
    }
};

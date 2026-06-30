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
        if (!Schema::hasTable('outbox_entries')) {
            Schema::create('outbox_entries', function (Blueprint $table) {
                $table->id();
                $table->string('event_key')->index();
                $table->json('payload');
                $table->string('yayin_durumu', 30)->default('PENDING')->index();
                $table->integer('attempts')->default(0);
                $table->text('error_message')->nullable();
                $table->string('idempotency_key')->nullable()->unique();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbox_entries');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B-006 P5C — AIStorage kanonik tablo
 *
 * LocalMySQLProvider için AI pattern/data depolama.
 * Deprecated\AIStorage ghost'unun fiziksel karşılığı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_storages', function (Blueprint $table) {
            $table->id();

            $table->string('storage_key', 500)->unique()->comment('Benzersiz depolama anahtarı');
            $table->json('data')->comment('AI veri payload (pattern, result, cache)');
            $table->string('type', 50)->nullable()->comment('pattern, result, cache, ...'); // context7-ignore
            $table->string('context', 255)->nullable()->comment('Anahtar prefix context\'i');

            $table->timestamps();

            $table->index('type', 'ai_storage_type_idx'); // context7-ignore
            $table->index('context', 'ai_storage_context_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_storages');
    }
};

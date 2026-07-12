<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feature Pack — isimlendirme Context7: pack yerine bundle kullanılabilir
        Schema::create('feature_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('display_name');          // UI'da gösterilecek ad
            $table->text('description')->nullable();
            $table->string('icon')->nullable();     // x-icon name
            $table->string('color')->nullable();    // Tailwind color class

            // Hangi kategorilere uygulanabilir
            $table->json('kategori_ids')->nullable();    // [7, 8, 9] gibi
            $table->json('yayin_tipi_ids')->nullable();  // [16, 17] gibi

            $table->tinyInteger('aktiflik_durumu')->default(1);
            $table->integer('display_order')->default(0);

            // Paket kaç özellik içeriyor (cache)
            $table->unsignedInteger('feature_count')->default(0);

            $table->timestamps();

            $table->index(['aktiflik_durumu', 'display_order']);
            $table->index('slug');
        });

        // Feature Pack Item — paketteki özellikler
        Schema::create('feature_pack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ozellik_id')->nullable();          // doğrudan özellik FK

            // Alternatif: template üzerinden referans
            $table->string('template_ref')->nullable();         // 'Konut|Villa|Satılık' gibi key
            $table->string('field_slug')->nullable();           // hangi field'a atanacak

            $table->string('value')->nullable();                 // varsayılan değer (boolean: 1 veya text/value)
            $table->string('display_order')->default(0);
            $table->text('notes')->nullable();                   // danışman notu

            $table->timestamps();

            $table->index(['feature_pack_id', 'ozellik_id']);
        });

        // Feature Pack Uygulama Logu — audit trail
        Schema::create('feature_pack_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ilan_id')->nullable();
            $table->foreignId('user_id')->nullable();

            // Operasyon tipi
            $table->string('action'); // 'applied', 'removed', 'undo_applied'
            $table->string('scope');   // 'single', 'bulk', 'category', 'all'
            $table->unsignedInteger('affected_count')->default(1);

            $table->json('snapshot_before')->nullable();   // uygulamadan önceki durum
            $table->json('snapshot_after')->nullable();    // uygulamadan sonraki durum
            $table->json('diff')->nullable();              // değişiklik farkı

            $table->text('reason')->nullable();             // neden uygulandı
            $table->timestamp('rolled_back_at')->nullable();

            $table->timestamps();

            $table->index(['feature_pack_id', 'action']);
            $table->index(['ilan_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_pack_logs');
        Schema::dropIfExists('feature_pack_items');
        Schema::dropIfExists('feature_packs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateIlanTaslaklarTableV2 — Wizard State Engine Migration
 *
 * Bu migration, ilan oluşturma sürecindeki verileri merkezi olarak saklayan
 * 'ilan_taslaklar' tablosunu oluşturur veya günceller.
 *
 * @version 2.0.0
 * @sprint Phase 2
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ilan_taslaklar')) {
            $this->createIlanTaslaklarTable();
        } else {
            try {
                // Tablo varsa eksik kolonları ekle (safe update)
                Schema::table('ilan_taslaklar', function (Blueprint $table) {
                    if (!Schema::hasColumn('ilan_taslaklar', 'category_id')) {
                        $table->integer('category_id')->nullable()->after('ilan_id');
                    }
                    if (!Schema::hasColumn('ilan_taslaklar', 'yayin_tipi_id')) {
                        $table->integer('yayin_tipi_id')->nullable()->after('category_id');
                    }
                    if (!Schema::hasColumn('ilan_taslaklar', 'version')) {
                        $table->integer('version')->default(1)->after('taslak_durumu');
                    }
                });
            } catch (\Exception $e) {
                // Eğer hasTable true dönüp buna rağmen 'table not found' hatası alıyorsak (race condition/cache)
                // Tabloyu oluşturmayı tekrar dene.
                if (!Schema::hasTable('ilan_taslaklar')) {
                    $this->createIlanTaslaklarTable();
                }
            }
        }
    }

    /**
     * Create the table structure
     */
    private function createIlanTaslaklarTable(): void
    {
        Schema::create('ilan_taslaklar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('site_id')->nullable();
            $table->integer('ilan_id')->nullable()->comment('İşlem tamamlandığında bağlanan ilan ID');
            $table->integer('category_id')->nullable()->comment('Draft aşamasındaki kategori');
            $table->integer('yayin_tipi_id')->nullable()->comment('Draft aşamasındaki yayın tipi');
            $table->integer('step')->default(1);
            $table->json('payload')->nullable()->comment('Form verileri (field_slug => value)');
            $table->tinyInteger('taslak_durumu')->default(1)->comment('1: aktif, 2: tamamlandı, 0: iptal');
            $table->integer('version')->default(1)->comment('Concurrency control');
            $table->timestamps();

            $table->index(['user_id', 'taslak_durumu']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri dönüşte veriyi korumak için tabloyu silmiyoruz, 
        // ancak v2 ile gelen özel kolonları geri alabiliriz.
        Schema::table('ilan_taslaklar', function (Blueprint $table) {
            if (Schema::hasColumn('ilan_taslaklar', 'category_id')) {
                $table->dropColumn(['category_id', 'yayin_tipi_id', 'version']);
            }
        });
    }
};

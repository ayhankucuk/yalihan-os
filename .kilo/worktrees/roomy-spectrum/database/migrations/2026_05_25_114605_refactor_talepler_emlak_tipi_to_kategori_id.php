<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Context7 Kanonik Uyum: emlak_tipi → ana_kategori_id
 *
 * Oturum 40: Property Hub Şema Restorasyonu
 *
 * Amaç: talepler tablosundaki string-based emlak_tipi alanını
 * ilişkisel ana_kategori_id (FK) yapısına dönüştürmek.
 *
 * Strateji: Paralel kolon (backward compatibility)
 * - ana_kategori_id eklenir
 * - emlak_tipi korunur (deprecated olarak işaretlenir)
 * - Veri migrasyonu yapılır
 * - Gelecek sprint'te emlak_tipi kaldırılabilir
 */
return new class extends Migration
{
    /**
     * String → ID mapping (Context7 kanonik kategoriler)
     */
    private const EMLAK_TIPI_MAPPING = [
        'Arsa' => 1,
        'Konut' => 2,
        'Villa' => 3,
        'İşyeri' => 4,
        'Yazlık' => 5,
        'Daire' => 2,  // Konut kategorisi
        'Residence' => 2,  // Konut kategorisi
        'Apart' => 2,  // Konut kategorisi
        'Müstakil Ev' => 2,  // Konut kategorisi
        'Bina' => 4,  // İşyeri kategorisi
        'Ofis' => 4,  // İşyeri kategorisi
        'Dükkan' => 4,  // İşyeri kategorisi
        'Depo' => 4,  // İşyeri kategorisi
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Yeni kolonları ekle (idempotency check ile)
        Schema::table('talepler', function (Blueprint $table) {
            // Context7 Protection: Sadece kolon veritabanında fiziksel olarak eksikse ekle
            if (!Schema::hasColumn('talepler', 'ana_kategori_id')) {
                $table->unsignedBigInteger('ana_kategori_id')->nullable()->after('talep_tipi');
            }

            if (!Schema::hasColumn('talepler', 'alt_kategori_id')) {
                $table->unsignedBigInteger('alt_kategori_id')->nullable()->after('ana_kategori_id');
            }
        });

        // 2. Foreign key constraints ekle (idempotency check ile)
        Schema::table('talepler', function (Blueprint $table) {
            // Check if foreign keys don't exist before adding
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('talepler');
            $existingFKs = array_map(fn($fk) => $fk->getName(), $foreignKeys);

            if (!in_array('talepler_ana_kategori_id_foreign', $existingFKs)) {
                $table->foreign('ana_kategori_id', 'talepler_ana_kategori_id_foreign')
                    ->references('id')
                    ->on('ilan_kategorileri')
                    ->nullOnDelete();
            }

            if (!in_array('talepler_alt_kategori_id_foreign', $existingFKs)) {
                $table->foreign('alt_kategori_id', 'talepler_alt_kategori_id_foreign')
                    ->references('id')
                    ->on('ilan_kategorileri')
                    ->nullOnDelete();
            }
        });

        // 2. Veri migrasyonu
        $this->migrateEmlakTipiData();

        // 3. emlak_tipi kolonunu nullable yap (backward compatibility için koru)
        Schema::table('talepler', function (Blueprint $table) {
            $table->string('emlak_tipi')->nullable()->comment('DEPRECATED: Use ana_kategori_id instead')->change();
        });

        Log::info('Context7: talepler.emlak_tipi → ana_kategori_id migration completed', [
            'migrated_count' => DB::table('talepler')->whereNotNull('ana_kategori_id')->count(),
            'total_count' => DB::table('talepler')->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Foreign key'leri kaldır
        Schema::table('talepler', function (Blueprint $table) {
            $table->dropForeign(['ana_kategori_id']);
            $table->dropForeign(['alt_kategori_id']);
        });

        // 2. Kolonları kaldır
        Schema::table('talepler', function (Blueprint $table) {
            $table->dropColumn(['ana_kategori_id', 'alt_kategori_id']);
        });

        // 3. emlak_tipi'yi geri not null yap
        Schema::table('talepler', function (Blueprint $table) {
            $table->string('emlak_tipi')->nullable(false)->comment('')->change();
        });

        Log::info('Context7: talepler migration rolled back');
    }

    /**
     * Mevcut emlak_tipi verilerini ana_kategori_id'ye migrate et
     */
    private function migrateEmlakTipiData(): void
    {
        $migratedCount = 0;
        $unmappedValues = [];

        foreach (self::EMLAK_TIPI_MAPPING as $emlakTipi => $kategoriId) {
            $count = DB::table('talepler')
                ->where('emlak_tipi', $emlakTipi)
                ->update(['ana_kategori_id' => $kategoriId]);

            $migratedCount += $count;

            if ($count > 0) {
                Log::info("Context7: Migrated emlak_tipi", [
                    'emlak_tipi' => $emlakTipi,
                    'ana_kategori_id' => $kategoriId,
                    'count' => $count,
                ]);
            }
        }

        // Eşleşmeyen değerleri tespit et
        $unmapped = DB::table('talepler')
            ->whereNotNull('emlak_tipi')
            ->whereNull('ana_kategori_id')
            ->select('emlak_tipi', DB::raw('COUNT(*) as count'))
            ->groupBy('emlak_tipi')
            ->get();

        if ($unmapped->isNotEmpty()) {
            Log::warning('Context7: Unmapped emlak_tipi values found', [
                'unmapped' => $unmapped->toArray(),
                'action_required' => 'Manual mapping needed or set to default category',
            ]);
        }

        Log::info('Context7: emlak_tipi data migration summary', [
            'migrated_count' => $migratedCount,
            'unmapped_count' => $unmapped->sum('count'),
        ]);
    }
};

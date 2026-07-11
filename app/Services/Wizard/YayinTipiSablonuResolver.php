<?php

namespace App\Services\Wizard;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * YayinTipiSablonuResolver — Tek kaynak yayın tipi → şablon çözümleyicisi.
 *
 * SAAB v8.0 Sprint 6.9 — ID sözleşmesi düzeltmesi
 *
 * SORUN:
 *  - Wizard gönderiyor: yayin_tipleri.id (1, 2, 3 — Satılık, Kiralık, etc.)
 *  - Policy bekliyor: yayin_tipi_sablonlari.id (junction/template ID)
 *  - Aynı tablo yok — iki ayrı tablo var: yayin_tipleri ve yayin_tipi_sablonlari
 *
 * COZUM:
 *  yayin_tipi_id (yayin_tipleri tablosundan) → yayin_tipi_sablonu_id (yayin_tipi_sablonlari tablosundan)
 *
 * ID TURLERI:
 *  - yayin_tipleri.id = 1, 2, 3, 4 — yayın tipi (Satılık, Kiralık, etc.)
 *  - yayin_tipi_sablonlari.id = 13, 14, 15... — junction/template ID
 *
 * IMZA:
 *  resolveTemplateId(int $mainCategoryId, int $subCategoryId, int $publicationTypeId): int
 *
 * KULLANIM:
 *  $sablonId = $resolver->resolveTemplateId(mainCategoryId: 1, subCategoryId: 8, publicationTypeId: 1);
 *  // Villa + Satılık → 13
 */
class YayinTipiSablonuResolver
{
    /**
     * Yayın tipi ID'sini (yayin_tipleri tablosundan) şablon ID'sine çözümler.
     *
     * SAAB v8.0 Sprint 6.9: Akıllı zincirleme fallback:
     *  1. Alt kategori + yayın tipi → doğrudan eşleşme
     *  2. Ana kategori + yayın tipi → parent'a çık
     *  3. Zincir üstündeki ilk eşleşme → property'nin category zincirini takip et
     *
     * Örnek:
     *  Villa (id=8, parent=1) + Satılık (yayin_tipi=1)
     *   → Arsa Satılık (sablon 13) ?  ❌ Yanlış sonuç — alanlar farklı
     *
     *  Konut (id=1) + Satılık (yayin_tipi=1)
     *   → Yok, parent'a bak
     *   → Konut.parent = null → HATA
     *
     * @param int $mainCategoryId  Ana kategori ID (seviye=0)
     * @param int|null $subCategoryId  Alt kategori ID (seviye=1, nullable)
     * @param int $publicationTypeId  Yayın tipi ID (yayin_tipleri tablosundan: 1=Satılık, 2=Kiralık, etc.)
     * @return int YayinTipiSablonu ID
     * @throws InvalidArgumentException Geçerli şablon bulunamazsa
     */
    public function resolveTemplateId(
        int $mainCategoryId,
        ?int $subCategoryId,
        int $publicationTypeId
    ): int {
        // Adım 1: Yayın tipi bilgilerini doğrula
        $yayinTipi = DB::table('yayin_tipleri')->where('id', $publicationTypeId)->first();
        if (!$yayinTipi) {
            throw new InvalidArgumentException("Geçersiz yayın tipi ID: {$publicationTypeId}");
        }

        $query = DB::table('yayin_tipi_sablonlari')
            ->where('yayin_tipi_id', $publicationTypeId)
            ->where('aktiflik_durumu', 1)
            ->whereNotNull('kategori_id')
            ->whereNotNull('yayin_tipi_id');

        // Adım 2: Önce alt kategori (en spesifik) — Villa + Satılık
        if ($subCategoryId !== null) {
            $sablon = (clone $query)->where('kategori_id', $subCategoryId)->first();
            if ($sablon) {
                return (int) $sablon->id;
            }
        }

        // Adım 3: Ana kategori — Konut + Satılık
        $sablon = (clone $query)->where('kategori_id', $mainCategoryId)->first();
        if ($sablon) {
            return (int) $sablon->id;
        }

        // Adım 4: Parent kategori zinciri takip et — Villa → Konut → Arsa
        $targetKategoriId = $subCategoryId ?? $mainCategoryId;
        $kategori = IlanKategori::find($targetKategoriId);

        if ($kategori && $kategori->parent_id) {
            $sablon = (clone $query)->where('kategori_id', $kategori->parent_id)->first();
            if ($sablon) {
                return (int) $sablon->id;
            }
        }

        // Adım 5: Hiçbir yerde junction bulunamadı
        // SAAB v8.0 Sprint 6.10: -1 fallback KALDIRILDI
        // Eksik veri = veri gap — sessiz fallback YOK
        throw new InvalidArgumentException(
            "Yayın tipi [{$publicationTypeId} ({$yayinTipi->name}) " .
            "için kategori [{$subCategoryId}/{$mainCategoryId}] kombinasyonunda " .
            "yayin_tipi_sablonlari junction kaydı bulunamadı. " .
            "Eksik veriyi kapatmak için YayinTipiSablonuCanonicalSeeder calistir."
        );
    }

    /**
     * Yayın tipi ID'sinin şablon ID'sine çözülüp çözülemeyeceğini kontrol eder.
     *
     * @param int $mainCategoryId
     * @param int|null $subCategoryId
     * @param int $publicationTypeId
     * @return bool
     */
    public function canResolve(int $mainCategoryId, ?int $subCategoryId, int $publicationTypeId): bool
    {
        /** @sab-ignore-catch */
        try {
            $this->resolveTemplateId($mainCategoryId, $subCategoryId, $publicationTypeId);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Bir kategorinin belirli bir yayın tipinde şablonu var mı?
     *
     * @param int $kategoriId  IlanKategori ID
     * @param int $publicationTypeId  Yayın tipi ID (yayin_tipleri tablosundan)
     * @return bool
     */
    public function hasTemplate(int $kategoriId, int $publicationTypeId): bool
    {
        return DB::table('yayin_tipi_sablonlari')
            ->where('yayin_tipi_id', $publicationTypeId)
            ->where('kategori_id', $kategoriId)
            ->where('aktiflik_durumu', 1)
            ->exists();
    }

    /**
     * Şablon ID'sinden tam şablon bilgisini getirir.
     *
     * @param int $sablonId  YayinTipiSablonu ID
     * @return array{sablon_id: int, kategori_id: int, yayin_tipi_id: int, yayin_tipi_name: string, kategori_name: string}
     */
    public function getTemplateInfo(int $sablonId): array
    {
        $sablon = YayinTipiSablonu::find($sablonId);

        if (!$sablon) {
            throw new InvalidArgumentException("Şablon bulunamadı: {$sablonId}");
        }

        $yayinTipi = DB::table('yayin_tipleri')->where('id', $sablon->yayin_tipi_id)->first();
        $kategori = IlanKategori::find($sablon->kategori_id);

        return [
            'sablon_id' => (int) $sablon->id,
            'kategori_id' => (int) $sablon->kategori_id,
            'yayin_tipi_id' => (int) $sablon->yayin_tipi_id,
            'yayin_tipi_name' => $yayinTipi?->name ?? "Yayın Tipi #{$sablon->yayin_tipi_id}",
            'kategori_name' => $kategori?->name ?? "Kategori #{$sablon->kategori_id}",
        ];
    }

    /**
     * Resolves the template/junction ID to extract concrete publication_type_id.
     */
    public function resolvePublicationTypeId(int $rawPublicationTypeId): int
    {
        $template = DB::table('yayin_tipi_sablonlari')->where('id', $rawPublicationTypeId)->first();
        return $template && !empty($template->yayin_tipi_id) ? (int) $template->yayin_tipi_id : $rawPublicationTypeId;
    }

    /**
     * Resolves yayin_tipi slug.
     */
    public function resolveYayinTipiSlug(int $publicationTypeId): string
    {
        $row = DB::table('yayin_tipleri')->where('id', $publicationTypeId)->first();
        return $row?->slug ?? 'genel';
    }
}

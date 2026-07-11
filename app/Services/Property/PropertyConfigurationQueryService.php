<?php

namespace App\Services\Property;

use App\Contracts\PropertyConfigurationContract;
use App\DTOs\ChannelConfigDTO;
use App\DTOs\FieldSchemaDTO;
use App\DTOs\PropertyConfigurationDTO;
use App\Models\KategoriYayinTipiFieldDependency;

/**
 * Property Configuration Query Service — Sprint 6.8
 *
 * Konut → Alt Kategori → Yayın Tipi kombinasyonu için
 * dinamik alan şeması sağlar.
 *
 * Kaynak tablo: kategori_yayin_tipi_field_dependencies (42 kayıt)
 *
 * SAAB Certified: Sprint 6.8
 * Kontrat: PropertyConfigurationContract
 */
class PropertyConfigurationQueryService implements PropertyConfigurationContract
{
    /**
     * 4 kanalın statik konfigürasyonu.
     * Canonical kaynak: Bu sınıf — gelecekte DB/registry'ye taşınabilir.
     *
     * @see Sprint 6.8 Note: "Statik channel mappings — geçici olabilir,
     *   canonical kaynak haline gelmemeli. Daha sonra configuration veya
     *   channel registry üzerinden gelmeli."
     */
    private const CHANNELS = [
        [
            'canonical' => 'Yalıhan',
            'display' => 'Yalıhan Emlak',
            'default' => true,
            'capabilities' => ['ai', 'photos', 'description', 'pricing'], // context7-ignore
        ],
        [
            'canonical' => 'Sahibinden',
            'display' => 'Sahibinden.com',
            'default' => false,
            'capabilities' => ['photos', 'description'], // context7-ignore
        ],
        [
            'canonical' => 'EMF',
            'display' => 'Emlak Merkezi Formu',
            'default' => false,
            'capabilities' => ['form', 'lead'],
        ],
        [
            'canonical' => 'Emlakkulisi',
            'display' => 'Emlakkulisi',
            'default' => false,
            'capabilities' => ['listing'],
        ],
    ];

    /**
     * Yayın tipi slug → canonical slug eşlemesi.
     * Sistemde hem slug hem de string tabanlı yayın tipi kullanılıyor.
     */
    private const LISTING_TYPE_MAP = [
        'satilik' => 'satilik',
        'kiralik' => 'kiralik',
        'kat-karsiligi' => 'kat_karsiligi',
        'devren' => 'devren',
    ];

    /**
     * Alt kategori slug → kategori eşlemesi.
     * Villa → konut, Daire → konut, Arsa → arsa-arazi, etc.
     */
    private const SUBCATEGORY_MAP = [
        'villa' => 'konut',
        'daire' => 'konut',
        'rezidans' => 'konut',
        'mustakil-ev' => 'konut',
        'bina' => 'konut',
        'ars arazi' => 'arsa-arazi',
        'tarla' => 'arsa-arazi',
        'ticari' => 'isyeri',
        'dukkan' => 'isyeri',
        'ofis' => 'isyeri',
        'yazlik' => 'yazlik-kiralama',
    ];

    /**
     * @inheritDoc
     */
    public function getConfiguration(
        string $category,
        string $subCategory,
        string $listingType,
    ): PropertyConfigurationDTO {
        $canonicalCategory = $this->resolveCategory($category, $subCategory);
        $canonicalListingType = $this->resolveListingType($listingType);

        $rows = KategoriYayinTipiFieldDependency::query()
            ->aktif()
            ->forKategoriYayinTipi($canonicalCategory, $canonicalListingType)
            ->ordered()
            ->get();

        $channels = $this->buildChannelConfigs();

        return PropertyConfigurationDTO::fromDependencyRows(
            category: $category,
            subCategory: $subCategory,
            listingType: $listingType,
            rows: $rows,
            channels: $channels,
        );
    }

    /**
     * @inheritDoc
     */
    public function getFields(
        string $category,
        string $subCategory,
        string $listingType,
    ): array {
        $canonicalCategory = $this->resolveCategory($category, $subCategory);
        $canonicalListingType = $this->resolveListingType($listingType);

        $rows = KategoriYayinTipiFieldDependency::query()
            ->aktif()
            ->forKategoriYayinTipi($canonicalCategory, $canonicalListingType)
            ->ordered()
            ->get();

        return $rows
            ->map(fn($row) => FieldSchemaDTO::fromModel($row))
            ->all();
    }

    /**
     * @inheritDoc
     */
    public function getAvailableCombinations(): array
    {
        $rows = KategoriYayinTipiFieldDependency::query()
            ->aktif()
            ->select('kategori_slug', 'yayin_tipi', 'yayin_tipi_id')
            ->selectRaw('COUNT(*) as field_count')
            ->groupBy('kategori_slug', 'yayin_tipi', 'yayin_tipi_id')
            ->orderBy('kategori_slug')
            ->get();

        return $rows->map(fn($row) => [
            'kategori' => $row->kategori_slug,
            'alt_kategori' => $this->guessSubCategory($row->kategori_slug),
            'yayin_tipi' => $row->yayin_tipi,
            'field_count' => (int) $row->field_count,
        ])->all();
    }

    /**
     * Kategori + alt kategori birleşiminden canonical kategori slug'ini çıkarır.
     * Eğer doğrudan kategori slug verilmişse onu döndürür.
     */
    private function resolveCategory(string $category, string $subCategory): string
    {
        // Doğrudan eşleşme kontrolü
        if (isset(self::SUBCATEGORY_MAP[$subCategory])) {
            return self::SUBCATEGORY_MAP[$subCategory];
        }

        // Alt kategori slug'ini küçük провercase ile kontrol et
        $normalized = strtolower($subCategory);
        if (isset(self::SUBCATEGORY_MAP[$normalized])) {
            return self::SUBCATEGORY_MAP[$normalized];
        }

        // Alt kategoriden kategori tahmini
        if ($category !== $subCategory) {
            return strtolower($category);
        }

        // Doğrudan slug olarak dön
        return strtolower($category);
    }

    /**
     * Yayın tipi string'ini canonical slug'e çevirir.
     */
    private function resolveListingType(string $listingType): string
    {
        $normalized = strtolower(trim($listingType));

        if (isset(self::LISTING_TYPE_MAP[$normalized])) {
            return self::LISTING_TYPE_MAP[$normalized];
        }

        // Sayısal ID kontrolü
        if (is_numeric($normalized)) {
            return $this->resolveListingTypeById((int) $normalized);
        }

        return $normalized;
    }

    /**
     * Yayın tipi ID'sinden slug çıkarır.
     */
    private function resolveListingTypeById(int $id): string
    {
        $map = [
            1 => 'satilik',
            2 => 'kiralik',
            3 => 'kat_karsiligi',
            4 => 'devren',
        ];

        return $map[$id] ?? 'satilik';
    }

    /**
     * Kategori slug'inden muhtemel alt kategori slug'ini tahmin eder.
     * Kesin sonuç için junction tablosu kullanılmalı (ileride).
     */
    private function guessSubCategory(string $categorySlug): string
    {
        $map = [
            'konut' => 'villa',
            'arsa-arazi' => 'arsa',
            'isyeri' => 'ticari',
            'yazlik-kiralama' => 'yazlik',
        ];

        return $map[$categorySlug] ?? 'genel';
    }

    /**
     * Kanal konfigürasyonlarını oluşturur.
     *
     * @return ChannelConfigDTO[]
     */
    private function buildChannelConfigs(): array
    {
        return array_map(
            fn(array $c) => new ChannelConfigDTO(
                canonicalName: $c['canonical'],
                displayName: $c['display'],
                isDefault: $c['default'],
                isActive: true,
                capabilities: $c['capabilities'],
            ),
            self::CHANNELS,
        );
    }
}

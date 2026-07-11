<?php

namespace App\Contracts;

use App\DTOs\PropertyConfigurationDTO;

/**
 * Property Configuration Contract — Sprint 6.8
 *
 * Konut → Alt Kategori → Yayın Tipi kombinasyonu için
 * dinamik alan şeması döndüren kontrat.
 *
 * Kaynak: kategori_yayin_tipi_field_dependencies (42 kayıt)
 *
 * SAAB Certified: Sprint 6.7
 */
interface PropertyConfigurationContract
{
    /**
     * Belirli bir kategori + alt kategori + yayın tipi kombinasyonu için
     * alan şemasını döndürür.
     *
     * @param string $category      Canonical kategori slug (örn. "konut")
     * @param string $subCategory   Alt kategori slug (örn. "villa")
     * @param string $listingType  Yayın tipi slug (örn. "satilik")
     * @return PropertyConfigurationDTO
     *
     * @throws \InvalidArgumentException Geçersiz kombinasyon
     */
    public function getConfiguration(
        string $category,
        string $subCategory,
        string $listingType,
    ): PropertyConfigurationDTO;

    /**
     * Sadece field listesini döndürür (kanal olmadan).
     *
     * @param string $category
     * @param string $subCategory
     * @param string $listingType
     * @return \App\DTOs\FieldSchemaDTO[]
     */
    public function getFields(
        string $category,
        string $subCategory,
        string $listingType,
    ): array;

    /**
     * Mevcut kombinasyonları listeler.
     *
     * @return array<array{category:string, sub_category:string, listing_type:string, field_count:int}>
     */
    public function getAvailableCombinations(): array;
}

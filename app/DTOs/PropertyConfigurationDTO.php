<?php

namespace App\DTOs;

use App\Models\KategoriYayinTipiFieldDependency;

/**
 * Property Configuration DTO — Sprint 6.8
 *
 * Konut → Alt Kategori → Yayın Tipi kombinasyonu için alan şeması taşıyan DTO.
 * Kaynak tablo: kategori_yayin_tipi_field_dependencies (42 kayıt)
 */
final class PropertyConfigurationDTO
{
    /**
     * @param string             $kategori     Canonical kategori slug (örn. "konut")
     * @param string             $altKategori  Alt kategori slug (örn. "villa")
     * @param string             $yayinTipi    Yayın tipi slug (örn. "satilik")
     * @param FieldSchemaDTO[]   $fields       Dinamik alan şeması
     * @param ChannelConfigDTO[] $channels     Kanal konfigürasyonu
     * @param array              $metadata     Ek meta veriler
     */
    public function __construct(
        public readonly string               $kategori,
        public readonly string               $altKategori,
        public readonly string               $yayinTipi,
        public readonly array                $fields,
        public readonly array                $channels,
        public readonly array                $metadata = [],
    ) {}

    /**
     * Dependency rows'lardan DTO oluştur.
     *
     * @param iterable|\App\Models\KategoriYayinTipiFieldDependency[] $rows
     * @return self
     */
    public static function fromDependencyRows(
        string $kategori,
        string $altKategori,
        string $yayinTipi,
        iterable $rows,
        array $channels = [],
    ): self {
        $fields = [];

        foreach ($rows as $row) {
            $fields[] = FieldSchemaDTO::fromModel($row);
        }

        return new self(
            kategori: $kategori,
            altKategori: $altKategori,
            yayinTipi: $yayinTipi,
            fields: $fields,
            channels: $channels,
            metadata: [
                'field_count' => count($fields),
                'required_count' => count(array_filter($fields, fn($f) => $f->isRequired)),
                'optional_count' => count(array_filter($fields, fn($f) => !$f->isRequired)),
                'ai_auto_fill_count' => count(array_filter($fields, fn($f) => $f->aiAutoFill)),
                'source_table' => 'kategori_yayin_tipi_field_dependencies',
            ],
        );
    }

    public function toArray(): array
    {
        return [
            'kategori' => $this->kategori,
            'alt_kategori' => $this->altKategori,
            'yayin_tipi' => $this->yayinTipi,
            'fields' => array_map(fn($f) => $f->toArray(), $this->fields),
            'channels' => array_map(fn($c) => $c->toArray(), $this->channels),
            'metadata' => $this->metadata,
        ];
    }
}

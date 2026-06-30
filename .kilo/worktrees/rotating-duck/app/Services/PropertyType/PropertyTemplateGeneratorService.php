<?php

namespace App\Services\PropertyType;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * UPS Template Generator Service
 *
 * @deprecated Bu servis PropertyTypeConfiguration Aggregate Root lehine kullanımdan kaldırılmıştır.
 * Template cozumleme islemleri icin \App\Domain\PropertyHub\PropertyTypeConfiguration::resolveTemplate() kullaniniz.
 *
 * @see \App\Domain\PropertyHub\PropertyTypeConfiguration
 */
class PropertyTemplateGeneratorService
{
    private array $templates;

    /** @var array<string, array> O(1) lookup index: "kategori|yayin_tipi|alt_tur" => template */
    private array $index = [];

    public function __construct()
    {
        $this->loadTemplates();
        $this->buildIndex();
    }

    /**
     * Load templates from JSON file
     */
    private function loadTemplates(): void
    {
        $this->templates = Cache::remember('ups_templates_data', 3600, function () {
            $path = config_path('ups_templates.json');

            if (!File::exists($path)) {
                throw new \RuntimeException('UPS Templates JSON not found: ' . $path);
            }

            $json = File::get($path);
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON in UPS Templates: ' . json_last_error_msg());
            }

            return $data['template_listesi'] ?? [];
        });
    }

    /**
     * Build O(1) lookup index from templates array
     */
    private function buildIndex(): void
    {
        foreach ($this->templates as $template) {
            $k = $template['kombinasyon'] ?? [];
            $key = ($k['kategori'] ?? '') . '|' . ($k['yayin_tipi'] ?? '') . '|' . ($k['alt_tur'] ?? '');
            $this->index[$key] = $template;
        }
    }

    /**
     * Generate template for given combination
     *
     * @param string $kategori Kategori adı (örn: "Konut", "Arsa & Arazi")
     * @param string $yayinTipi Yayın tipi (örn: "Satılık", "Günlük Kiralama")
     * @param string $altTur Alt tür (örn: "Daire", "Villa", "Tarla")
     * @return array|null Template data or null if not found
     */
    public function generate(string $kategori, string $yayinTipi, string $altTur): ?array
    {
        $key = $kategori . '|' . $yayinTipi . '|' . $altTur;

        return $this->index[$key] ?? null;
    }

    /**
     * Get all available templates
     *
     * @return array
     */
    public function getAllTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Get required fields for combination
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @return array
     */
    public function getRequiredFields(string $kategori, string $yayinTipi, string $altTur): array
    {
        $template = $this->generate($kategori, $yayinTipi, $altTur);
        return $template['zorunlu_alanlar'] ?? [];
    }

    /**
     * Get optional fields for combination
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @return array
     */
    public function getOptionalFields(string $kategori, string $yayinTipi, string $altTur): array
    {
        $template = $this->generate($kategori, $yayinTipi, $altTur);
        return $template['opsiyonel_alanlar'] ?? [];
    }

    /**
     * Get hidden fields for combination
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @return array
     */
    public function getHiddenFields(string $kategori, string $yayinTipi, string $altTur): array
    {
        $template = $this->generate($kategori, $yayinTipi, $altTur);
        return $template['gizli_alanlar'] ?? [];
    }

    /**
     * Get validation rules for combination
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @return array
     */
    public function getValidationRules(string $kategori, string $yayinTipi, string $altTur): array
    {
        $template = $this->generate($kategori, $yayinTipi, $altTur);
        return $template['validasyon_kurallari'] ?? [];
    }

    /**
     * Get UI hints for combination
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @return array
     */
    public function getUIHints(string $kategori, string $yayinTipi, string $altTur): array
    {
        $template = $this->generate($kategori, $yayinTipi, $altTur);
        return $template['ui_ipuclari'] ?? [];
    }

    /**
     * Get conditional rules for combination
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @return array
     */
    public function getConditionalRules(string $kategori, string $yayinTipi, string $altTur): array
    {
        $template = $this->generate($kategori, $yayinTipi, $altTur);
        return $template['kosullu_kurallar'] ?? [];
    }

    /**
     * Get AI price suggestion logic for combination
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @return array|null
     */
    public function getAIPriceSuggestion(string $kategori, string $yayinTipi, string $altTur): ?array
    {
        $template = $this->generate($kategori, $yayinTipi, $altTur);
        return $template['ai_fiyat_onerisi'] ?? null;
    }

    /**
     * Get enum options for fields
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @return array
     */
    public function getEnums(string $kategori, string $yayinTipi, string $altTur): array
    {
        $template = $this->generate($kategori, $yayinTipi, $altTur);
        return $template['enumlar'] ?? [];
    }

    /**
     * Get enum options for a specific field
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @param string $fieldName
     * @return array|null
     */
    public function getFieldEnum(string $kategori, string $yayinTipi, string $altTur, string $fieldName): ?array
    {
        $enums = $this->getEnums($kategori, $yayinTipi, $altTur);
        return $enums[$fieldName] ?? null;
    }

    /**
     * Check if field should be visible for combination
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @param string $fieldName
     * @return bool
     */
    public function isFieldVisible(string $kategori, string $yayinTipi, string $altTur, string $fieldName): bool
    {
        $hiddenFields = $this->getHiddenFields($kategori, $yayinTipi, $altTur);
        return !in_array($fieldName, $hiddenFields);
    }

    /**
     * Check if field is required for combination
     *
     * @param string $kategori
     * @param string $yayinTipi
     * @param string $altTur
     * @param string $fieldName
     * @return bool
     */
    public function isFieldRequired(string $kategori, string $yayinTipi, string $altTur, string $fieldName): bool
    {
        $requiredFields = $this->getRequiredFields($kategori, $yayinTipi, $altTur);
        return in_array($fieldName, $requiredFields);
    }
}

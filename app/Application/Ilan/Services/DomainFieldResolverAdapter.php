<?php

declare(strict_types=1);

namespace App\Application\Ilan\Services;

use App\Domain\Ilan\Policies\CategoryFieldPolicy;
use App\Domain\Ilan\ValueObjects\FieldDefinition as DomainFieldDefinition;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Wizard\FieldEngine\FieldDefinition as LegacyFieldDefinition;

/**
 * DomainFieldResolverAdapter — Application Adapter Bridge
 *
 * Connects pure Domain CategoryFieldPolicy to legacy Wizard FieldEngine DTOs
 * without introducing framework dependencies into the Domain layer.
 */
class DomainFieldResolverAdapter
{
    public function __construct(
        private readonly CategoryFieldPolicy $policy
    ) {}

    /**
     * Resolve legacy FieldDefinition DTOs from pure Domain Policy using slugs.
     *
     * @return LegacyFieldDefinition[]
     */
    public function resolveBySlug(string $kategoriSlug, string $yayinTipiSlug): array
    {
        $domainDefinitions = $this->policy->getFieldDefinitions($kategoriSlug, $yayinTipiSlug);

        return array_map(
            fn(DomainFieldDefinition $def) => $this->toLegacyDefinition($def),
            $domainDefinitions
        );
    }

    /**
     * Resolve legacy FieldDefinition DTOs using category and publication type IDs.
     *
     * @return LegacyFieldDefinition[]
     */
    public function resolve(int $kategoriId, int $yayinTipiId): array
    {
        $kategori = IlanKategori::find($kategoriId);
        $yayinTipi = YayinTipiSablonu::find($yayinTipiId);

        if (!$kategori || !$yayinTipi) {
            return [];
        }

        $kategoriSlug = $kategori->slug ?? 'konut';
        $yayinTipiSlug = $yayinTipi->slug ?? 'satilik';

        return $this->resolveBySlug($kategoriSlug, $yayinTipiSlug);
    }

    /**
     * Convert pure Domain FieldDefinition to legacy Wizard FieldEngine FieldDefinition.
     */
    public function toLegacyDefinition(DomainFieldDefinition $domainDef): LegacyFieldDefinition
    {
        $rule = $domainDef->rule();
        $rawOptions = $rule->options();

        $formattedOptions = null;
        if (!empty($rawOptions)) {
            $formattedOptions = array_values(array_map(function ($opt) {
                if (is_array($opt) && isset($opt['value'], $opt['label'])) {
                    return [
                        'value' => \Illuminate\Support\Str::slug((string) $opt['value']),
                        'label' => (string) $opt['label'],
                    ];
                }
                $label = is_string($opt) ? $opt : (string) $opt;
                return [
                    'value' => \Illuminate\Support\Str::slug($label),
                    'label' => $label,
                ];
            }, $rawOptions));
        }

        return new LegacyFieldDefinition(
            slug: $domainDef->key()->value(),
            name: $domainDef->name(),
            type: $rule->type(),
            category: $domainDef->category(),
            required: $rule->isRequired(),
            display_order: $domainDef->displayOrder(),
            options: $formattedOptions,
            unit: $rule->unit(),
            icon: $domainDef->icon(),
            placeholder: $domainDef->placeholder(),
            helpText: $domainDef->helpText(),
            visibleIf: $domainDef->visibleIf(),
            requiredIf: $domainDef->requiredIf(),
            dependsOn: $domainDef->dependsOn(),
            aiAutoFill: $domainDef->isAiAutoFill(),
            aiSuggestion: $domainDef->isAiSuggestion(),
            aiPromptKey: $domainDef->aiPromptKey(),
            searchable: $domainDef->isSearchable(),
            showInCard: $domainDef->showInCard(),
            min: $rule->min(),
            max: $rule->max(),
            step: $rule->step(),
            id: null
        );
    }
}

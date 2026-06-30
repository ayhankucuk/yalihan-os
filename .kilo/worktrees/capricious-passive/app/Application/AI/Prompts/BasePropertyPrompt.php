<?php

namespace App\Application\AI\Prompts;

use App\Domain\AI\Contracts\PromptInterface;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;

/**
 * ️ BasePropertyPrompt
 * 
 * Abstract base class for all property-related AI prompts.
 * Provides shared context building for categories and template types.
 */
abstract class BasePropertyPrompt implements PromptInterface
{
    /**
     * @param array $input Original request input data.
     */
    public function __construct(
        protected readonly array $input
    ) {}

    public function getSystemInstructions(): string
    {
        return "Sen uzman bir gayrimenkul veri analisti ve veritabanı mimarısın. 
Görevlerini Türkçe dilinde, profesyonel bir üslupla ve kesinlikle istenen teknik formatta yerine getirmelisin.";
    }

    public function getJSONSchema(): ?array
    {
        return null; // Override in children if specific schema is needed
    }

    public function getOptions(): array
    {
        return [
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ];
    }

    /**
     * Helper to resolve Category Name from ID if not provided.
     */
    protected function resolveCategoryName(?int $id, ?string $name): string
    {
        if ($name) return $name;
        if ($id) return IlanKategori::find($id)?->name ?? 'Gayrimenkul';
        return 'Gayrimenkul';
    }

    /**
     * Helper to resolve Template Type name.
     */
    protected function resolveTemplateName(?int $id): string
    {
        if ($id) return YayinTipiSablonu::find($id)?->ad ?? 'Genel Şablon';
        return 'Genel Şablon';
    }
}

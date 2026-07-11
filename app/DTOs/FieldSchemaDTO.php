<?php

namespace App\DTOs;

use App\Models\KategoriYayinTipiFieldDependency;

/**
 * Field Schema DTO — Tek bir dinamik alanı temsil eder.
 */
final class FieldSchemaDTO
{
    public function __construct(
        public readonly string  $slug,
        public readonly string  $name,
        public readonly string  $tip,
        public readonly string  $kategori,
        public readonly bool    $isRequired,
        public readonly ?string $unit,
        public readonly ?string $icon,
        public readonly ?array  $options,
        public readonly int     $displayOrder,
        public readonly bool    $aiAutoFill,
        public readonly bool    $aiSuggestion,
        public readonly bool    $searchable,
        public readonly bool    $showInCard,
        public readonly bool    $aktiflikDurumu,
    ) {}

    public static function fromModel(KategoriYayinTipiFieldDependency $model): self
    {
        return new self(
            slug: $model->field_slug,
            name: $model->field_name,
            tip: $model->field_type ?? 'text',
            kategori: $model->field_category ?? 'general',
            isRequired: (bool) $model->required,
            unit: $model->field_unit,
            icon: $model->field_icon,
            options: is_array($model->field_options) ? $model->field_options : null,
            displayOrder: (int) $model->display_order,
            aiAutoFill: (bool) ($model->ai_auto_fill ?? false),
            aiSuggestion: (bool) ($model->ai_suggestion ?? false),
            searchable: (bool) ($model->searchable ?? false),
            showInCard: (bool) ($model->show_in_card ?? false),
            aktiflikDurumu: (bool) ($model->aktiflik_durumu ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'tip' => $this->tip,
            'kategori' => $this->kategori,
            'required' => $this->isRequired,
            'unit' => $this->unit,
            'icon' => $this->icon,
            'options' => $this->options,
            'display_order' => $this->displayOrder,
            'ai_auto_fill' => $this->aiAutoFill,
            'ai_suggestion' => $this->aiSuggestion,
            'searchable' => $this->searchable,
            'show_in_card' => $this->showInCard,
            'aktiflik_durumu' => $this->aktiflikDurumu,
        ];
    }
}

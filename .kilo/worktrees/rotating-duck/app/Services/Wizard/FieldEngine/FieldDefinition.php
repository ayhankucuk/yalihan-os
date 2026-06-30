<?php

namespace App\Services\Wizard\FieldEngine;

use Illuminate\Support\Str;

/**
 * FieldDefinition — Immutable DTO for a single form field.
 *
 * DB satırından (KategoriYayinTipiFieldDependency) üretilir.
 * Tüm wizard engine katmanları bu DTO üzerinden çalışır:
 *   FieldResolver → FieldDefinition[] → DependencyEvaluator → FieldRenderer → Blade
 *
 * Bu sınıf:
 *  - DB'ye bağımlı DEĞİL (pure data object)
 *  - JSON serialize edilebilir (API response)
 *  - Frontend Alpine.js engine ile 1:1 simetrik
 */
final class FieldDefinition
{
    public function __construct(
        /** Unique field identifier (DB slug) */
        public readonly string $slug,

        /** Human-readable label */
        public readonly string $name,

        /** Field type: text, number, select, boolean, multiselect, textarea */
        public readonly string $type,

        /** Field grouping category: temel, fiziksel, altyapi, finansal, ek_ozellikler */
        public readonly string $category,

        /** Is field required by default */
        public readonly bool $required,

        /** Display position within group */
        public readonly int $display_order,

        /** Select/multiselect options: [{value, label}] */
        public readonly ?array $options,

        /** Unit suffix (m², TL, TL/ay, gece...) */
        public readonly ?string $unit,

        /** Emoji icon */
        public readonly ?string $icon,

        /** Placeholder text */
        public readonly ?string $placeholder,

        /** Help/description text */
        public readonly ?string $helpText,

        /** Dependency: visible_if rule {field, operator, value} */
        public readonly ?array $visibleIf,

        /** Dependency: required_if rule {field, operator, value} */
        public readonly ?array $requiredIf,

        /** Dependency: depends_on parent slug (simpler than visible_if) */
        public readonly ?string $dependsOn,

        /** AI can auto-fill this field */
        public readonly bool $aiAutoFill,

        /** AI can suggest values */
        public readonly bool $aiSuggestion,

        /** AI prompt key for contextual suggestions */
        public readonly ?string $aiPromptKey,

        /** Show in search filters */
        public readonly bool $searchable,

        /** Show in listing card */
        public readonly bool $showInCard,

        /** Validation min value (for number type) */
        public readonly int|float|null $min,

        /** Validation max value (for number/text type) */
        public readonly int|float|null $max,

        /** Number step (for number type) */
        public readonly int|float|null $step,

        /** DB record ID (for updates) */
        public readonly ?int $id,
    ) {}

    /**
     * Create from DB row (KategoriYayinTipiFieldDependency model attributes).
     */
    public static function fromDbRow(array $row): self
    {
        $options = is_string($row['field_options'] ?? null)
            ? json_decode($row['field_options'], true)
            : ($row['field_options'] ?? []);

        $normalizedOptions = self::normalizeOptions(
            $options['items'] ?? $options['choices'] ?? $options['values'] ?? null,
            $row['field_type'] ?? 'text'
        );

        return new self(
            slug: $row['field_slug'],
            name: $row['field_name'],
            type: $row['field_type'] ?? 'text',
            category: $row['field_category'] ?? 'general',
            required: (bool) ($row['required'] ?? false),
            display_order: (int) ($row['display_order'] ?? 999),
            options: $normalizedOptions,
            unit: $row['field_unit'] ?? null,
            icon: $row['field_icon'] ?? null,
            placeholder: $options['placeholder'] ?? null,
            helpText: $options['help_text'] ?? null,
            visibleIf: $options['visible_if'] ?? null,
            requiredIf: $options['required_if'] ?? null,
            dependsOn: $options['depends_on'] ?? null,
            aiAutoFill: (bool) ($row['ai_auto_fill'] ?? false),
            aiSuggestion: (bool) ($row['ai_suggestion'] ?? false),
            aiPromptKey: $row['ai_prompt_key'] ?? null,
            searchable: (bool) ($row['searchable'] ?? false),
            showInCard: (bool) ($row['show_in_card'] ?? false),
            min: $options['min'] ?? null,
            max: $options['max'] ?? null,
            step: $options['step'] ?? null,
            id: $row['id'] ?? null,
        );
    }

    /**
     * Normalize options for select/multiselect: string[] → [{value, label}]
     */
    private static function normalizeOptions(?array $items, string $type): ?array
    {
        if (!in_array($type, ['select', 'multiselect', 'dropdown', 'tags'])) {
            return null;
        }

        if (empty($items)) {
            return null;
        }

        return array_values(array_map(function ($item) {
            if (is_array($item) && isset($item['value'], $item['label'])) {
                return $item;
            }
            $label = is_string($item) ? $item : (string) $item;
            return [
                'value' => Str::slug($label),
                'label' => $label,
            ];
        }, $items));
    }

    /**
     * Convert to array for JSON/API response.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type, // context7-ignore
            'category' => $this->category,
            'required' => $this->required,
            'display_order' => $this->display_order,
            'options' => $this->options,
            'unit' => $this->unit,
            'icon' => $this->icon,
            'placeholder' => $this->placeholder,
            'help_text' => $this->helpText,
            'visible_if' => $this->visibleIf,
            'required_if' => $this->requiredIf,
            'depends_on' => $this->dependsOn,
            'ai_auto_fill' => $this->aiAutoFill,
            'ai_suggestion' => $this->aiSuggestion,
            'ai_prompt_key' => $this->aiPromptKey,
            'searchable' => $this->searchable,
            'show_in_card' => $this->showInCard,
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
        ];
    }

    /**
     * Get the Blade component name for this field type.
     */
    public function getComponentName(): string
    {
        return match ($this->type) {
            'number', 'integer', 'decimal', 'float' => 'input-number',
            'boolean', 'toggle' => 'toggle',
            'select', 'dropdown' => 'select',
            'multiselect', 'tags' => 'multiselect',
            'textarea', 'longtext' => 'textarea',
            default => 'input-text',
        };
    }

    /**
     * Does this field have any dependency rules?
     */
    public function hasDependencies(): bool
    {
        return $this->visibleIf !== null
            || $this->requiredIf !== null
            || $this->dependsOn !== null;
    }

    /**
     * Is this field AI-capable (auto-fill or suggestion)?
     */
    public function isAiCapable(): bool
    {
        return $this->aiAutoFill || $this->aiSuggestion;
    }
}

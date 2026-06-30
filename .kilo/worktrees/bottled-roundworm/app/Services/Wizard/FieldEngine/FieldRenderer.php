<?php

namespace App\Services\Wizard\FieldEngine;

use App\Services\Wizard\DependencyRuleEvaluator;
use Illuminate\Support\Facades\View;

/**
 * FieldRenderer — FieldDefinition[] → HTML çıktısı.
 *
 * Sorumluluk:
 *  - FieldDefinition type'ını Blade component'e map etmek
 *  - Dependency kurallarını evaluate ederek visible/required belirlemek
 *  - Grouped rendering için field'ları gruplamak
 *  - Tek bir render loop ile tüm field'ları HTML'e çevirmek
 *
 * Kullanım:
 *   $renderer = app(FieldRenderer::class);
 *   $html = $renderer->render($fields, $formValues);
 *   // veya
 *   $groups = $renderer->renderGrouped($fields, $formValues);
 *
 * Blade Component Map:
 *   text       → components.fields.input-text
 *   number     → components.fields.input-number
 *   boolean    → components.fields.toggle
 *   select     → components.fields.select
 *   multiselect → components.fields.multiselect
 *   textarea   → components.fields.textarea
 */
class FieldRenderer
{
    /**
     * Blade component directory prefix.
     */
    private const VIEW_PREFIX = 'components.fields.';

    /**
     * type → blade component name mapping.
     */
    private const TYPE_MAP = [
        'text' => 'input-text',
        'string' => 'input-text',
        'number' => 'input-number',
        'integer' => 'input-number',
        'decimal' => 'input-number',
        'float' => 'input-number',
        'boolean' => 'toggle',
        'toggle' => 'toggle',
        'select' => 'select',
        'dropdown' => 'select',
        'multiselect' => 'multiselect',
        'tags' => 'multiselect',
        'textarea' => 'textarea',
        'longtext' => 'textarea',
    ];

    public function __construct(
        private readonly DependencyRuleEvaluator $dependencyEvaluator,
    ) {}

    /**
     * Render all fields as HTML string.
     *
     * @param FieldDefinition[] $fields All field definitions
     * @param array $formValues Current form values {slug => value}
     * @return string Rendered HTML
     */
    public function render(array $fields, array $formValues = []): string
    {
        $html = '';
        $knownSlugs = array_map(fn (FieldDefinition $f) => $f->slug, $fields);

        foreach ($fields as $field) {
            // Evaluate visibility
            $fieldArray = $field->toArray();
            if (!$this->dependencyEvaluator->isVisible($fieldArray, $formValues, $knownSlugs)) {
                continue;
            }

            // Evaluate effective required state
            $effectiveRequired = $this->dependencyEvaluator->isRequired($fieldArray, $formValues, $knownSlugs);

            $html .= $this->renderField($field, $formValues[$field->slug] ?? '', $effectiveRequired);
        }

        return $html;
    }

    /**
     * Render fields grouped by category.
     *
     * @param FieldDefinition[] $fields All field definitions
     * @param array $formValues Current form values {slug => value}
     * @return array<int, array{name: string, slug: string, html: string, field_count: int}>
     */
    public function renderGrouped(array $fields, array $formValues = []): array
    {
        $knownSlugs = array_map(fn (FieldDefinition $f) => $f->slug, $fields);

        // Group fields by category
        $groups = [];
        foreach ($fields as $field) {
            $cat = $field->category;
            if (!isset($groups[$cat])) {
                $groups[$cat] = [
                    'name' => $this->getCategoryLabel($cat),
                    'slug' => $cat,
                    'fields' => [],
                ];
            }
            $groups[$cat]['fields'][] = $field;
        }

        // Render each group
        $result = [];
        foreach ($groups as $group) {
            $groupHtml = '';
            $visibleCount = 0;

            foreach ($group['fields'] as $field) {
                $fieldArray = $field->toArray();
                if (!$this->dependencyEvaluator->isVisible($fieldArray, $formValues, $knownSlugs)) {
                    continue;
                }

                $effectiveRequired = $this->dependencyEvaluator->isRequired($fieldArray, $formValues, $knownSlugs);
                $groupHtml .= $this->renderField($field, $formValues[$field->slug] ?? '', $effectiveRequired);
                $visibleCount++;
            }

            // Only include groups with visible fields
            if ($visibleCount > 0) {
                $result[] = [
                    'name' => $group['name'],
                    'slug' => $group['slug'],
                    'html' => $groupHtml,
                    'field_count' => $visibleCount,
                ];
            }
        }

        return $result;
    }

    /**
     * Render a single field to HTML using its Blade component.
     *
     * @param FieldDefinition $field Field definition DTO
     * @param mixed $value Current field value
     * @param bool $effectiveRequired Resolved required state (base + required_if)
     * @return string Rendered HTML
     */
    public function renderField(FieldDefinition $field, mixed $value = '', bool $effectiveRequired = false): string
    {
        $componentName = $field->getComponentName();
        $viewName = self::VIEW_PREFIX . $componentName;

        // Fallback to input-text if view doesn't exist
        if (!View::exists($viewName)) {
            $viewName = self::VIEW_PREFIX . 'input-text';
        }

        // Build field data array for the blade component
        // Override required with effective required (dependency-aware)
        $fieldData = $field->toArray();
        $fieldData['required'] = $effectiveRequired || $field->required;

        return View::make($viewName, [
            'field' => $fieldData,
            'value' => $value,
        ])->render();
    }

    /**
     * Get the Blade component name for a field type.
     *
     * @param string $type Field type
     * @return string Blade component name (without prefix)
     */
    public static function getComponentForType(string $type): string
    {
        return self::TYPE_MAP[$type] ?? 'input-text';
    }

    /**
     * Get the full Blade view name for a field type.
     *
     * @param string $type Field type
     * @return string Full view name
     */
    public static function getViewForType(string $type): string
    {
        return self::VIEW_PREFIX . self::getComponentForType($type);
    }

    /**
     * Get all supported field types.
     */
    public static function getSupportedTypes(): array
    {
        return array_keys(self::TYPE_MAP);
    }

    /**
     * Category slug → human label.
     */
    private function getCategoryLabel(string $slug): string
    {
        return match ($slug) {
            'temel', 'general' => 'Temel Bilgiler',
            'fiziksel' => 'Fiziksel Özellikler',
            'altyapi' => 'Altyapı',
            'finansal' => 'Finansal Bilgiler',
            'konum' => 'Konum Detayları',
            'isyeri' => 'İşyeri Detayları',
            'kiralama' => 'Kiralama Bilgileri',
            'ek_ozellikler' => 'Ek Özellikler',
            default => 'Genel',
        };
    }
}

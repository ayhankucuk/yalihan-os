<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use Tests\TestCase;

/**
 * Class DynamicFieldEngineTest
 *
 * Sprint 6.1-E05: DynamicFieldEngine Blade Component Feature Tests
 *
 * Tests rendering of dynamic workspace inputs based on template schemas,
 * including validation feedback and type handling.
 *
 * @package Tests\Feature\Workspace
 */
class DynamicFieldEngineTest extends TestCase
{
    /**
     * Test single field component rendering for different field types.
     */
    public function test_renders_text_field(): void
    {
        $field = [
            'key' => 'baslik',
            'label' => 'İlan Başlığı',
            'alan_tipi' => 'text',
            'required' => true,
            'max' => 120,
        ];

        $view = $this->blade('<x-workspace.dynamic-field :field="$field" :value="$value" :isMissing="$isMissing" />', [
            'field' => $field,
            'value' => 'Villa Bodrum',
            'isMissing' => false,
        ]);

        $view->assertSee('İlan Başlığı');
        $view->assertSee('value="Villa Bodrum"', false);
        $view->assertSee('required', false);
        $view->assertSee('maxlength="120"', false);
    }

    public function test_renders_textarea_field(): void
    {
        $field = [
            'key' => 'aciklama',
            'label' => 'Açıklama',
            'alan_tipi' => 'textarea',
            'required' => true,
            'max' => 5000,
        ];

        $view = $this->blade('<x-workspace.dynamic-field :field="$field" :value="$value" :isMissing="$isMissing" />', [
            'field' => $field,
            'value' => 'Detaylı açıklama',
            'isMissing' => false,
        ]);

        $view->assertSee('Açıklama');
        $view->assertSee('Detaylı açıklama');
        $view->assertSee('maxlength="5000"', false);
    }

    public function test_renders_select_field(): void
    {
        $field = [
            'key' => 'para_birimi',
            'label' => 'Para Birimi',
            'alan_tipi' => 'select',
            'required' => true,
            'options' => ['TRY', 'USD', 'EUR'],
        ];

        $view = $this->blade('<x-workspace.dynamic-field :field="$field" :value="$value" :isMissing="$isMissing" />', [
            'field' => $field,
            'value' => 'USD',
            'isMissing' => false,
        ]);

        $view->assertSee('Para Birimi');
        $view->assertSee('value="TRY"', false);
        $view->assertSee('value="USD" selected', false);
    }

    public function test_renders_number_field(): void
    {
        $field = [
            'key' => 'fiyat',
            'label' => 'Fiyat',
            'alan_tipi' => 'number',
            'required' => true,
            'min' => 0,
        ];

        $view = $this->blade('<x-workspace.dynamic-field :field="$field" :value="$value" :isMissing="$isMissing" />', [
            'field' => $field,
            'value' => 1500000,
            'isMissing' => false,
        ]);

        $view->assertSee('Fiyat');
        $view->assertSee('type="number"', false);
        $view->assertSee('value="1500000"', false);
        $view->assertSee('min="0"', false);
    }

    public function test_renders_boolean_field(): void
    {
        $field = [
            'key' => 'esyali',
            'label' => 'Eşyalı mı?',
            'alan_tipi' => 'boolean',
            'required' => false,
        ];

        $view = $this->blade('<x-workspace.dynamic-field :field="$field" :value="$value" :isMissing="$isMissing" />', [
            'field' => $field,
            'value' => true,
            'isMissing' => false,
        ]);

        $view->assertSee('Eşyalı mı?');
        $view->assertSee('checked', false);
    }

    public function test_renders_image_field_with_existing_value(): void
    {
        $field = [
            'key' => 'kapak_resmi',
            'label' => 'Kapak Resmi',
            'alan_tipi' => 'image',
            'required' => true,
        ];

        $view = $this->blade('<x-workspace.dynamic-field :field="$field" :value="$value" :isMissing="$isMissing" />', [
            'field' => $field,
            'value' => 'villa.jpg',
            'isMissing' => false,
        ]);

        $view->assertSee('Kapak Resmi');
        $view->assertSee('villa.jpg');
        $view->assertSee('Yüklü');
    }

    public function test_renders_missing_field_with_red_highlight_and_badge(): void
    {
        $field = [
            'key' => 'baslik',
            'label' => 'İlan Başlığı',
            'alan_tipi' => 'text',
            'required' => true,
        ];

        $view = $this->blade('<x-workspace.dynamic-field :field="$field" :value="$value" :isMissing="$isMissing" />', [
            'field' => $field,
            'value' => null,
            'isMissing' => true,
        ]);

        $view->assertSee('İlan Başlığı');
        $view->assertSee('Eksik');
        $view->assertSee('bg-red-50/50', false); // Highlight class check
    }

    /**
     * Test wrapper dynamic-fields component.
     */
    public function test_renders_dynamic_fields_wrapper_with_readiness_payload(): void
    {
        $fields = [
            [
                'key' => 'baslik',
                'label' => 'İlan Başlığı',
                'alan_tipi' => 'text',
                'required' => true,
            ],
            [
                'key' => 'fiyat',
                'label' => 'Fiyat',
                'alan_tipi' => 'number',
                'required' => true,
            ],
        ];

        $values = [
            'baslik' => 'Bodrum Villa',
            'fiyat' => null,
        ];

        $readiness = [
            'intent' => 'satilik',
            'template_id' => 'tpl_satilik',
            'readiness_score' => 50,
            'readiness_status' => 'incomplete',
            'missing_fields' => ['fiyat'],
            'missing_documents' => [],
            'missing_ai_hooks' => [],
            'summary' => 'Incomplete (score: 50/100). 1 required field(s) missing: fiyat.',
        ];

        $view = $this->blade('<x-workspace.dynamic-fields :fields="$fields" :values="$values" :readiness="$readiness" />', [
            'fields' => $fields,
            'values' => $values,
            'readiness' => $readiness,
        ]);

        $view->assertSee('Yayın Hazırlık Durumu');
        $view->assertSee('Incomplete (score: 50/100). 1 required field(s) missing: fiyat.');
        $view->assertSee('incomplete');
        $view->assertSee('Bodrum Villa');
        $view->assertSee('Eksik'); // for 'fiyat' field
    }
}

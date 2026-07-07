<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Workspace;

use App\Services\Workspace\TemplateEngineService;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Class TemplateEngineServiceTest
 *
 * Sprint 6.1-E02: TemplateEngineService Unit Tests
 *
 * @package Tests\Unit\Services\Workspace
 */
class TemplateEngineServiceTest extends TestCase
{
    private TemplateEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TemplateEngineService();
    }

    // ─────────────────────────────────────────────────────────
    // resolveTemplate — Structural Tests
    // ─────────────────────────────────────────────────────────

    public function test_resolve_template_returns_expected_keys(): void
    {
        $result = $this->service->resolveTemplate('satilik');

        $this->assertArrayHasKey('intent', $result);
        $this->assertArrayHasKey('template_id', $result);
        $this->assertArrayHasKey('fields', $result);
        $this->assertArrayHasKey('readiness_rules', $result);
        $this->assertArrayHasKey('required_documents', $result);
        $this->assertArrayHasKey('ai_hooks', $result);
        $this->assertArrayHasKey('requires_calendar', $result);
    }

    public function test_resolve_satilik_sets_correct_intent_and_template_id(): void
    {
        $result = $this->service->resolveTemplate('satilik');

        $this->assertEquals('satilik', $result['intent']);
        $this->assertEquals('tpl_satilik', $result['template_id']);
    }

    public function test_resolve_sezonluk_sets_correct_intent_and_template_id(): void
    {
        $result = $this->service->resolveTemplate('sezonluk');

        $this->assertEquals('sezonluk', $result['intent']);
        $this->assertEquals('tpl_sezonluk', $result['template_id']);
    }

    // ─────────────────────────────────────────────────────────
    // Base Fields
    // ─────────────────────────────────────────────────────────

    public function test_all_intents_include_baslik_field(): void
    {
        foreach ($this->service->getSupportedIntents() as $intent) {
            $result = $this->service->resolveTemplate($intent);
            $keys = array_column($result['fields'], 'key');
            $this->assertContains('baslik', $keys, "Intent '{$intent}' is missing 'baslik' field");
        }
    }

    public function test_all_intents_include_fiyat_field(): void
    {
        foreach ($this->service->getSupportedIntents() as $intent) {
            $result = $this->service->resolveTemplate($intent);
            $keys = array_column($result['fields'], 'key');
            $this->assertContains('fiyat', $keys, "Intent '{$intent}' is missing 'fiyat' field");
        }
    }

    public function test_all_intents_include_kapak_resmi_field(): void
    {
        foreach ($this->service->getSupportedIntents() as $intent) {
            $result = $this->service->resolveTemplate($intent);
            $keys = array_column($result['fields'], 'key');
            $this->assertContains('kapak_resmi', $keys, "Intent '{$intent}' is missing 'kapak_resmi' field");
        }
    }

    // ─────────────────────────────────────────────────────────
    // Intent-specific Fields
    // ─────────────────────────────────────────────────────────

    public function test_satilik_includes_tapu_field(): void
    {
        $result = $this->service->resolveTemplate('satilik');
        $keys = array_column($result['fields'], 'key');

        $this->assertContains('tapusu_var', $keys);
        $this->assertContains('brut_metrekare', $keys);
        $this->assertContains('oda_sayisi', $keys);
    }

    public function test_kiralik_includes_kira_donem_field(): void
    {
        $result = $this->service->resolveTemplate('kiralik');
        $keys = array_column($result['fields'], 'key');

        $this->assertContains('kira_donem', $keys);
        $this->assertContains('depozito', $keys);
    }

    public function test_sezonluk_includes_calendar_and_kapasite_fields(): void
    {
        $result = $this->service->resolveTemplate('sezonluk');
        $keys = array_column($result['fields'], 'key');

        $this->assertContains('musait_tarihler', $keys);
        $this->assertContains('kapasite', $keys);
        $this->assertContains('min_konaklama', $keys);
    }

    // ─────────────────────────────────────────────────────────
    // Readiness Rules
    // ─────────────────────────────────────────────────────────

    public function test_satilik_readiness_rules_include_required_fields(): void
    {
        $result = $this->service->resolveTemplate('satilik');

        $this->assertContains('baslik', $result['readiness_rules']);
        $this->assertContains('fiyat', $result['readiness_rules']);
        $this->assertContains('brut_metrekare', $result['readiness_rules']);
        $this->assertContains('tapusu_var', $result['readiness_rules']);
    }

    public function test_all_intents_have_non_empty_readiness_rules(): void
    {
        foreach ($this->service->getSupportedIntents() as $intent) {
            $result = $this->service->resolveTemplate($intent);
            $this->assertNotEmpty(
                $result['readiness_rules'],
                "Intent '{$intent}' has empty readiness_rules"
            );
        }
    }

    // ─────────────────────────────────────────────────────────
    // AI Hooks
    // ─────────────────────────────────────────────────────────

    public function test_satilik_has_generate_title_ai_hook(): void
    {
        $result = $this->service->resolveTemplate('satilik');

        $this->assertContains('generate_title', $result['ai_hooks']);
        $this->assertContains('generate_description', $result['ai_hooks']);
    }

    public function test_sezonluk_has_translation_ai_hook(): void
    {
        $result = $this->service->resolveTemplate('sezonluk');

        $this->assertContains('translate_to_english', $result['ai_hooks']);
        $this->assertContains('generate_calendar_block', $result['ai_hooks']);
    }

    // ─────────────────────────────────────────────────────────
    // Calendar Flag
    // ─────────────────────────────────────────────────────────

    public function test_requires_calendar_is_false_for_satilik(): void
    {
        $result = $this->service->resolveTemplate('satilik');

        $this->assertFalse($result['requires_calendar']);
    }

    public function test_requires_calendar_is_true_for_sezonluk(): void
    {
        $result = $this->service->resolveTemplate('sezonluk');

        $this->assertTrue($result['requires_calendar']);
    }

    public function test_requires_calendar_is_true_for_gunluk(): void
    {
        $result = $this->service->resolveTemplate('gunluk');

        $this->assertTrue($result['requires_calendar']);
    }

    public function test_requires_calendar_is_true_for_haftalik(): void
    {
        $result = $this->service->resolveTemplate('haftalik');

        $this->assertTrue($result['requires_calendar']);
    }

    // ─────────────────────────────────────────────────────────
    // Legacy / Alias Intents (canonicalization)
    // ─────────────────────────────────────────────────────────

    public function test_yazlik_alias_resolves_to_sezonluk(): void
    {
        // YayinTipiRules maps 'yazlik' → 'sezonluk'
        $result = $this->service->resolveTemplate('yazlik');

        $this->assertEquals('sezonluk', $result['intent']);
        $this->assertEquals('tpl_sezonluk', $result['template_id']);
    }

    public function test_gunluk_kiralik_alias_resolves_to_gunluk(): void
    {
        $result = $this->service->resolveTemplate('gunluk-kiralik');

        $this->assertEquals('gunluk', $result['intent']);
    }

    // ─────────────────────────────────────────────────────────
    // Unknown Intent
    // ─────────────────────────────────────────────────────────

    public function test_unknown_intent_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->resolveTemplate('bilinmeyen-tip');
    }

    // ─────────────────────────────────────────────────────────
    // supports() helper
    // ─────────────────────────────────────────────────────────

    public function test_supports_returns_true_for_known_intents(): void
    {
        $this->assertTrue($this->service->supports('satilik'));
        $this->assertTrue($this->service->supports('kiralik'));
        $this->assertTrue($this->service->supports('sezonluk'));
    }

    public function test_supports_returns_false_for_unknown_intent(): void
    {
        $this->assertFalse($this->service->supports('bilinmeyen-tip'));
    }

    public function test_supports_handles_legacy_alias(): void
    {
        // yazlik → sezonluk → supported
        $this->assertTrue($this->service->supports('yazlik'));
    }

    // ─────────────────────────────────────────────────────────
    // getSupportedIntents()
    // ─────────────────────────────────────────────────────────

    public function test_get_supported_intents_includes_core_types(): void
    {
        $intents = $this->service->getSupportedIntents();

        $this->assertContains('satilik', $intents);
        $this->assertContains('kiralik', $intents);
        $this->assertContains('sezonluk', $intents);
        $this->assertContains('gunluk', $intents);
    }

    public function test_get_supported_intents_returns_array(): void
    {
        $intents = $this->service->getSupportedIntents();

        $this->assertIsArray($intents);
        $this->assertNotEmpty($intents);
    }
}

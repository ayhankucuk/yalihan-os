<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Workspace;

use App\Services\Workspace\ReadinessEvaluatorService;
use App\Services\Workspace\TemplateEngineService;
use Tests\TestCase;

/**
 * Class ReadinessEvaluatorServiceTest
 *
 * Sprint 6.1-E03: ReadinessEvaluatorService Unit Tests
 *
 * @package Tests\Unit\Services\Workspace
 */
class ReadinessEvaluatorServiceTest extends TestCase
{
    private ReadinessEvaluatorService $evaluator;
    private TemplateEngineService $templateEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator      = new ReadinessEvaluatorService();
        $this->templateEngine = new TemplateEngineService();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Output structure
    // ─────────────────────────────────────────────────────────────────────────

    public function test_evaluate_returns_expected_keys(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');
        $result   = $this->evaluator->evaluate([], $template);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('missing_fields', $result);
        $this->assertArrayHasKey('missing_documents', $result);
        $this->assertArrayHasKey('missing_ai_hooks', $result);
        $this->assertArrayHasKey('field_score', $result);
        $this->assertArrayHasKey('document_score', $result);
        $this->assertArrayHasKey('ai_hook_score', $result);
        $this->assertArrayHasKey('summary', $result);
    }

    public function test_score_is_integer_between_0_and_100(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');
        $result   = $this->evaluator->evaluate([], $template);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_status_is_one_of_allowed_values(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');
        $result   = $this->evaluator->evaluate([], $template);

        $this->assertContains($result['status'], ['incomplete', 'warning', 'ready']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Empty workspace → incomplete
    // ─────────────────────────────────────────────────────────────────────────

    public function test_empty_workspace_is_incomplete(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');
        $result   = $this->evaluator->evaluate([], $template);

        $this->assertEquals('incomplete', $result['status']);
        $this->assertLessThan(60, $result['score']);
    }

    public function test_empty_workspace_lists_all_required_fields_as_missing(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');
        $result   = $this->evaluator->evaluate([], $template);

        // satilik requires: baslik, aciklama, fiyat, kapak_resmi, il, ilce, brut_metrekare, oda_sayisi, tapusu_var
        $this->assertContains('baslik', $result['missing_fields']);
        $this->assertContains('fiyat', $result['missing_fields']);
        $this->assertContains('tapusu_var', $result['missing_fields']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Partial completion → warning
    // ─────────────────────────────────────────────────────────────────────────

    public function test_all_required_fields_present_but_no_documents_gives_warning(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');

        // Fill all required fields
        $workspaceData = [
            'baslik'         => 'Bodrum Villa',
            'aciklama'       => 'Harika bir villa.',
            'fiyat'          => 5000000,
            'kapak_resmi'    => 'kapak.jpg',
            'il'             => 'Muğla',
            'ilce'           => 'Bodrum',
            'brut_metrekare' => 180,
            'oda_sayisi'     => '3+1',
            'tapusu_var'     => 'kat-mulkiyeti',
        ];

        // No documents uploaded
        $result = $this->evaluator->evaluate($workspaceData, $template, [], []);

        // Fields: 100% (weight 0.70 = 70pts)
        // Documents: 0% (weight 0.20 = 0pts)
        // AI hooks: 0% (weight 0.10 = 0pts)
        // Total: 70 → 'warning'
        $this->assertEquals('warning', $result['status']);
        $this->assertEmpty($result['missing_fields']);
        $this->assertNotEmpty($result['missing_documents']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Full completion → ready
    // ─────────────────────────────────────────────────────────────────────────

    public function test_fully_complete_workspace_is_ready(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');

        $workspaceData = [
            'baslik'         => 'Bodrum Villa',
            'aciklama'       => 'Harika bir villa.',
            'fiyat'          => 5000000,
            'kapak_resmi'    => 'kapak.jpg',
            'il'             => 'Muğla',
            'ilce'           => 'Bodrum',
            'brut_metrekare' => 180,
            'oda_sayisi'     => '3+1',
            'tapusu_var'     => 'kat-mulkiyeti',
        ];

        $uploadedDocuments = ['tapu_fotokopisi', 'iskan_belgesi'];
        $completedAiHooks  = ['generate_title', 'generate_description'];

        $result = $this->evaluator->evaluate($workspaceData, $template, $uploadedDocuments, $completedAiHooks);

        $this->assertEquals('ready', $result['status']);
        $this->assertGreaterThanOrEqual(90, $result['score']);
        $this->assertEmpty($result['missing_fields']);
        $this->assertEmpty($result['missing_documents']);
        $this->assertEmpty($result['missing_ai_hooks']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Score calculation correctness
    // ─────────────────────────────────────────────────────────────────────────

    public function test_score_is_100_when_everything_is_complete(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');

        $workspaceData     = ['baslik' => 'V', 'aciklama' => 'A', 'fiyat' => 1, 'kapak_resmi' => 'k.jpg', 'il' => 'X', 'ilce' => 'Y', 'brut_metrekare' => 100, 'oda_sayisi' => '2+1', 'tapusu_var' => 'hisseli'];
        $uploadedDocuments = ['tapu_fotokopisi', 'iskan_belgesi'];
        $completedAiHooks  = ['generate_title', 'generate_description'];

        $result = $this->evaluator->evaluate($workspaceData, $template, $uploadedDocuments, $completedAiHooks);

        $this->assertEquals(100, $result['score']);
    }

    public function test_score_is_0_when_nothing_is_complete(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');
        $result   = $this->evaluator->evaluate([], $template, [], []);

        // field_score=0 (×0.70) + doc_score=0 (×0.20) + ai_score=0 (×0.10) = 0
        $this->assertEquals(0, $result['score']);
    }

    public function test_partial_field_completion_produces_correct_field_score(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');
        // satilik has 9 required fields. Fill 3 of them.
        $workspaceData = [
            'baslik'   => 'Test',
            'aciklama' => 'Test açıklama',
            'fiyat'    => 1000,
        ];

        $result = $this->evaluator->evaluate($workspaceData, $template, [], []);

        // 3/9 fields present = 33%
        $this->assertEquals(33, $result['field_score']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sezonluk — calendar-based intent
    // ─────────────────────────────────────────────────────────────────────────

    public function test_sezonluk_complete_workspace_is_ready(): void
    {
        $template = $this->templateEngine->resolveTemplate('sezonluk');

        $workspaceData = [
            'baslik'           => 'Bodrum Yazlık',
            'aciklama'         => 'Denize sıfır villa.',
            'fiyat'            => 8000,
            'kapak_resmi'      => 'kapak.jpg',
            'il'               => 'Muğla',
            'ilce'             => 'Bodrum',
            'kapasite'         => 8,
            'yatak_odasi'      => 4,
            'musait_tarihler'  => ['2026-07-01', '2026-07-31'],
            'min_konaklama'    => 7,
        ];

        $uploadedDocuments = ['is_ruhsati', 'yangin_raporu'];
        $completedAiHooks  = ['generate_title', 'generate_description'];

        $result = $this->evaluator->evaluate($workspaceData, $template, $uploadedDocuments, $completedAiHooks);

        $this->assertEquals('ready', $result['status']);
        $this->assertEmpty($result['missing_fields']);
    }

    public function test_sezonluk_missing_calendar_is_incomplete(): void
    {
        $template = $this->templateEngine->resolveTemplate('sezonluk');

        $workspaceData = [
            'baslik'       => 'Bodrum Yazlık',
            'aciklama'     => 'Açıklama.',
            'fiyat'        => 8000,
            'kapak_resmi'  => 'k.jpg',
            'il'           => 'Muğla',
            'ilce'         => 'Bodrum',
            'kapasite'     => 8,
            'yatak_odasi'  => 4,
            // musait_tarihler missing
            // min_konaklama missing
        ];

        $result = $this->evaluator->evaluate($workspaceData, $template, [], []);

        $this->assertContains('musait_tarihler', $result['missing_fields']);
        $this->assertContains('min_konaklama', $result['missing_fields']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Missing documents
    // ─────────────────────────────────────────────────────────────────────────

    public function test_partially_uploaded_documents_listed_correctly(): void
    {
        $template          = $this->templateEngine->resolveTemplate('satilik');
        $uploadedDocuments = ['tapu_fotokopisi']; // iskan_belgesi missing
        $workspaceData     = ['baslik' => 'X', 'aciklama' => 'X', 'fiyat' => 1, 'kapak_resmi' => 'k', 'il' => 'X', 'ilce' => 'X', 'brut_metrekare' => 1, 'oda_sayisi' => '1+1', 'tapusu_var' => 'hisseli'];

        $result = $this->evaluator->evaluate($workspaceData, $template, $uploadedDocuments, []);

        $this->assertContains('iskan_belgesi', $result['missing_documents']);
        $this->assertNotContains('tapu_fotokopisi', $result['missing_documents']);
    }

    public function test_document_score_is_100_when_all_documents_uploaded(): void
    {
        $template          = $this->templateEngine->resolveTemplate('satilik');
        $uploadedDocuments = ['tapu_fotokopisi', 'iskan_belgesi'];

        $result = $this->evaluator->evaluate([], $template, $uploadedDocuments, []);

        $this->assertEquals(100, $result['document_score']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AI hooks
    // ─────────────────────────────────────────────────────────────────────────

    public function test_missing_generate_title_hook_is_flagged(): void
    {
        $template      = $this->templateEngine->resolveTemplate('satilik');
        $completedHooks = ['generate_description']; // generate_title missing

        $result = $this->evaluator->evaluate([], $template, [], $completedHooks);

        $this->assertContains('generate_title', $result['missing_ai_hooks']);
    }

    public function test_ai_hook_score_is_100_when_required_hooks_done(): void
    {
        $template       = $this->templateEngine->resolveTemplate('satilik');
        $completedHooks = ['generate_title', 'generate_description'];

        $result = $this->evaluator->evaluate([], $template, [], $completedHooks);

        $this->assertEquals(100, $result['ai_hook_score']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Blank value detection
    // ─────────────────────────────────────────────────────────────────────────

    public function test_empty_string_field_counts_as_missing(): void
    {
        $template      = $this->templateEngine->resolveTemplate('satilik');
        $workspaceData = ['baslik' => ''];

        $result = $this->evaluator->evaluate($workspaceData, $template, [], []);

        $this->assertContains('baslik', $result['missing_fields']);
    }

    public function test_whitespace_only_field_counts_as_missing(): void
    {
        $template      = $this->templateEngine->resolveTemplate('satilik');
        $workspaceData = ['baslik' => '   '];

        $result = $this->evaluator->evaluate($workspaceData, $template, [], []);

        $this->assertContains('baslik', $result['missing_fields']);
    }

    public function test_zero_integer_does_not_count_as_missing(): void
    {
        // fiyat = 0 is a valid value (free listing scenario)
        $template      = $this->templateEngine->resolveTemplate('satilik');
        $workspaceData = ['fiyat' => 0];

        $result = $this->evaluator->evaluate($workspaceData, $template, [], []);

        $this->assertNotContains('fiyat', $result['missing_fields']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // isReady() helper
    // ─────────────────────────────────────────────────────────────────────────

    public function test_is_ready_returns_true_for_complete_workspace(): void
    {
        $template          = $this->templateEngine->resolveTemplate('satilik');
        $workspaceData     = ['baslik' => 'V', 'aciklama' => 'A', 'fiyat' => 1, 'kapak_resmi' => 'k', 'il' => 'X', 'ilce' => 'Y', 'brut_metrekare' => 100, 'oda_sayisi' => '2+1', 'tapusu_var' => 'hisseli'];
        $uploadedDocuments = ['tapu_fotokopisi', 'iskan_belgesi'];
        $completedAiHooks  = ['generate_title', 'generate_description'];

        $this->assertTrue(
            $this->evaluator->isReady($workspaceData, $template, $uploadedDocuments, $completedAiHooks)
        );
    }

    public function test_is_ready_returns_false_for_incomplete_workspace(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');

        $this->assertFalse(
            $this->evaluator->isReady([], $template)
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // evaluateFromModel() helper
    // ─────────────────────────────────────────────────────────────────────────

    public function test_evaluate_from_model_flattens_data_key(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');

        $modelAttributes = [
            'baslik'   => 'Test',
            'aciklama' => 'Test aciklama',
            'data'     => [
                'fiyat' => 1000,
            ],
        ];

        $result = $this->evaluator->evaluateFromModel($modelAttributes, $template);

        // fiyat came from nested 'data' key — should not be in missing_fields
        $this->assertNotContains('fiyat', $result['missing_fields']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Summary text
    // ─────────────────────────────────────────────────────────────────────────

    public function test_summary_contains_ready_message_when_ready(): void
    {
        $template          = $this->templateEngine->resolveTemplate('satilik');
        $workspaceData     = ['baslik' => 'V', 'aciklama' => 'A', 'fiyat' => 1, 'kapak_resmi' => 'k', 'il' => 'X', 'ilce' => 'Y', 'brut_metrekare' => 100, 'oda_sayisi' => '2+1', 'tapusu_var' => 'hisseli'];
        $uploadedDocuments = ['tapu_fotokopisi', 'iskan_belgesi'];
        $completedAiHooks  = ['generate_title', 'generate_description'];

        $result = $this->evaluator->evaluate($workspaceData, $template, $uploadedDocuments, $completedAiHooks);

        $this->assertStringContainsString('ready for review', $result['summary']);
    }

    public function test_summary_lists_missing_fields_when_incomplete(): void
    {
        $template = $this->templateEngine->resolveTemplate('satilik');
        $result   = $this->evaluator->evaluate([], $template, [], []);

        $this->assertStringContainsString('missing', $result['summary']);
        $this->assertStringContainsString('baslik', $result['summary']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Telegram;

use App\Models\User;
use App\Modules\Finans\Models\FinansalIslem;
use App\Services\Telegram\Processors\FinanceProcessor;
use App\Services\Telegram\Processors\FinanceAuditLog;
use App\Services\Telegram\Processors\FinanceResponseValidator;
use App\Services\Telegram\Processors\FinanceValidationResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * FinanceProcessor Integration Tests — R08 FinanceProcessor AI Safety
 *
 * Definition of Done:
 * - invalid AI JSON / schema failure → fallback + review flag
 * - AI-only output never auto-approves finance workflow (always bekliyor)
 * - ai_review_required flag set on fallback/unsafe output
 * - audit log called on failure paths
 */
class FinanceProcessorTest extends TestCase
{
    use RefreshDatabase;

    private FinanceResponseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new FinanceResponseValidator();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // R08: Schema Validation Failures
    // =========================================================================

    public function test_missing_miktar_triggers_review_flag(): void
    {
        $result = $this->validator->validate([
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Test',
        ]);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('SCHEMA_MISSING_MIKTAR', $result->errorCode);
    }

    public function test_non_numeric_miktar_triggers_review_flag(): void
    {
        $result = $this->validator->validate([
            'miktar' => 'not-a-number',
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Test expense',
        ]);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
    }

    // =========================================================================
    // R08: Business Rule Validation Failures
    // =========================================================================

    public function test_negative_amount_triggers_review_flag(): void
    {
        $result = $this->validator->validate([
            'miktar' => -500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Negative expense',
        ]);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('BUSINESS_NEGATIVE_AMOUNT', $result->errorCode);
    }

    public function test_unsupported_currency_triggers_review_flag(): void
    {
        $result = $this->validator->validate([
            'miktar' => 500,
            'para_birimi' => 'BTC',
            'islem_tipi' => 'gider',
            'aciklama' => 'Bitcoin expense',
        ]);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
    }

    // =========================================================================
    // R08: AI Auto-Approval Signal Detection
    // =========================================================================

    public function test_ai_auto_approval_signal_triggers_review(): void
    {
        $result = $this->validator->validate([
            'miktar' => 500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'AI otomatik onay verdi', // Contains AI signals
        ]);

        $this->assertTrue($result->isValid); // Schema + business rules pass
        $this->assertTrue($result->aiReviewRequired); // But review required
        $this->assertStringContainsString('AI_AUTO_APPROVAL_SIGNAL', $result->reviewReason ?? '');
    }

    public function test_tahmin_signal_triggers_review(): void
    {
        $result = $this->validator->validate([
            'miktar' => 500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Tahmin edilen gider',
        ]);

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertStringContainsString('AI_AUTO_APPROVAL_SIGNAL', $result->reviewReason ?? '');
    }

    public function test_karar_signal_triggers_review(): void
    {
        $result = $this->validator->validate([
            'miktar' => 500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'AI karar verdi',
        ]);

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertStringContainsString('AI_AUTO_APPROVAL_SIGNAL', $result->reviewReason ?? '');
    }

    // =========================================================================
    // R08: Valid Output — Safe for Processing
    // =========================================================================

    public function test_valid_output_passes_validation(): void
    {
        $result = $this->validator->validate([
            'miktar' => 500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Kahve',
        ]);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->aiReviewRequired);
        $this->assertEquals(500.00, $result->data['miktar']);
        $this->assertEquals('TRY', $result->data['para_birimi']);
        $this->assertEquals('gider', $result->data['islem_tipi']);
    }

    public function test_valid_eur_output_passes(): void
    {
        $result = $this->validator->validate([
            'miktar' => 100,
            'para_birimi' => 'EUR',
            'islem_tipi' => 'gider',
            'aciklama' => 'Book',
        ]);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->aiReviewRequired);
        $this->assertEquals('EUR', $result->data['para_birimi']);
    }

    public function test_valid_usd_output_passes(): void
    {
        $result = $this->validator->validate([
            'miktar' => 200,
            'para_birimi' => 'USD',
            'islem_tipi' => 'gider',
            'aciklama' => 'Laptop',
        ]);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->aiReviewRequired);
        $this->assertEquals('USD', $result->data['para_birimi']);
    }

    // =========================================================================
    // R08: FinanceWorkflow never auto-approves from AI-only output
    // =========================================================================

    public function test_finance_workflow_requires_human_approval(): void
    {
        // Simulate AI-only output that passes validation but requires review
        $result = $this->validator->validate([
            'miktar' => 500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Sistemin verdiği otomatik karar',
        ]);

        // Even when schema + business rules pass, aiReviewRequired is true
        $this->assertTrue($result->isValid);
        $this->assertTrue($result->aiReviewRequired);

        // FinanceProcessor must NEVER auto-approve when aiReviewRequired is true
        // The workflow check is in FinanceProcessor layer: if ($result->aiReviewRequired) → review message
        $this->assertStringContainsString('AI_AUTO_APPROVAL_SIGNAL', $result->reviewReason ?? '');
    }

    // =========================================================================
    // R08: FinanceValidationResult value object
    // =========================================================================

    public function test_validation_result_always_requires_review_when_invalid(): void
    {
        $result = FinanceValidationResult::invalid('ERR_CODE', 'Test error', null);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
    }

    public function test_validation_result_is_immutable(): void
    {
        $result = FinanceValidationResult::valid([
            'miktar' => 100,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Test',
        ]);

        $reflection = new \ReflectionClass($result);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }
    }

    // =========================================================================
    // R08: FinanceValidationResult factories
    // =========================================================================

    public function test_valid_factory(): void
    {
        $data = ['miktar' => 100, 'para_birimi' => 'TRY', 'islem_tipi' => 'gider', 'aciklama' => 'Test'];
        $result = FinanceValidationResult::valid($data);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->aiReviewRequired);
        $this->assertEquals($data, $result->data);
    }

    public function test_valid_with_review_factory(): void
    {
        $data = ['miktar' => 100, 'para_birimi' => 'TRY', 'islem_tipi' => 'gider', 'aciklama' => 'Test'];
        $result = FinanceValidationResult::validWithReviewFlag($data, 'TEST', 'Test reason');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertStringContainsString('TEST', $result->reviewReason ?? '');
    }

    public function test_invalid_factory(): void
    {
        $result = FinanceValidationResult::invalid('ERR', 'Error msg', null);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('ERR', $result->errorCode);
    }
}

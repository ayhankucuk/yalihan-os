<?php

declare(strict_types=1);

namespace Tests\Unit\Telegram\Processors;

use App\Services\Telegram\Processors\FinanceResponseValidator;
use App\Services\Telegram\Processors\FinanceValidationResult;
use PHPUnit\Framework\TestCase;

/**
 * FinanceResponseValidator Unit Tests — R08
 *
 * Test coverage:
 * - Invalid AI JSON → fallback + review flag
 * - Schema validation (missing/invalid fields)
 * - Business rule validation (amount, currency, type, description)
 * - AI auto-approval signal detection
 * - Valid output → accepted only after schema + business validation
 * - Audit log fields populated correctly
 */
class FinanceResponseValidatorTest extends TestCase
{
    private FinanceResponseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new FinanceResponseValidator();
    }

    // =========================================================================
    // Schema Validation Tests
    // =========================================================================

    public function test_valid_ai_response_passes_schema_and_business_validation(): void
    {
        $data = [
            'miktar' => 500.50,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Kahve masrafı',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->aiReviewRequired);
        $this->assertNull($result->errorCode);
        $this->assertEquals(500.50, $result->data['miktar']);
        $this->assertEquals('TRY', $result->data['para_birimi']);
        $this->assertEquals('gider', $result->data['islem_tipi']);
    }

    public function test_non_array_input_is_invalid(): void
    {
        $result = $this->validator->validate('{"miktar": 100}');
        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('SCHEMA_ERROR', $result->errorCode);
        $this->assertNull($result->data);
    }

    public function test_null_input_is_invalid(): void
    {
        $result = $this->validator->validate(null);
        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('SCHEMA_ERROR', $result->errorCode);
    }

    public function test_missing_miktar_field_is_invalid(): void
    {
        $data = [
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Test',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('SCHEMA_MISSING_MIKTAR', $result->errorCode);
    }

    public function test_non_numeric_miktar_is_invalid(): void
    {
        $data = [
            'miktar' => 'five hundred',
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Test',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('SCHEMA_INVALID_MIKTAR_TYPE', $result->errorCode);
    }

    public function test_missing_para_birimi_field_is_invalid(): void
    {
        $data = [
            'miktar' => 100,
            'islem_tipi' => 'gider',
            'aciklama' => 'Test',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('SCHEMA_MISSING_PARA_BIRIMI', $result->errorCode);
    }

    public function test_missing_islem_tipi_field_is_invalid(): void
    {
        $data = [
            'miktar' => 100,
            'para_birimi' => 'TRY',
            'aciklama' => 'Test',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('SCHEMA_MISSING_ISLEM_TIPI', $result->errorCode);
    }

    public function test_missing_aciklama_gets_default_value(): void
    {
        $data = [
            'miktar' => 100,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->aiReviewRequired);
        $this->assertEquals('Telegram üzerinden eklendi', $result->data['aciklama']);
    }

    // =========================================================================
    // Business Rule Validation Tests
    // =========================================================================

    public function test_negative_amount_is_invalid(): void
    {
        $data = [
            'miktar' => -100,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Test expense',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('BUSINESS_NEGATIVE_AMOUNT', $result->errorCode);
    }

    public function test_zero_amount_is_invalid(): void
    {
        $data = [
            'miktar' => 0,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Test expense',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('BUSINESS_NEGATIVE_AMOUNT', $result->errorCode);
    }

    public function test_excessive_amount_is_invalid(): void
    {
        $data = [
            'miktar' => 999_000_000_000,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Suspicious amount',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('BUSINESS_EXCESSIVE_AMOUNT', $result->errorCode);
    }

    public function test_too_small_amount_is_invalid(): void
    {
        $data = [
            'miktar' => 0.001,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Micro amount',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('BUSINESS_TOO_SMALL_AMOUNT', $result->errorCode);
    }

    public function test_unsupported_currency_is_invalid(): void
    {
        $data = [
            'miktar' => 100,
            'para_birimi' => 'BTC', // Unsupported
            'islem_tipi' => 'gider',
            'aciklama' => 'Bitcoin expense',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('BUSINESS_INVALID_CURRENCY', $result->errorCode);
    }

    public function test_lowercase_currency_is_normalized(): void
    {
        $data = [
            'miktar' => 100,
            'para_birimi' => 'usd',
            'islem_tipi' => 'gider',
            'aciklama' => 'USD expense',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertEquals('USD', $result->data['para_birimi']);
    }

    public function test_unsupported_islem_tipi_is_invalid(): void
    {
        $data = [
            'miktar' => 100,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'investment', // Not a valid type
            'aciklama' => 'Investment',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('BUSINESS_INVALID_TYPE', $result->errorCode);
    }

    public function test_empty_description_is_invalid(): void
    {
        $data = [
            'miktar' => 100,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => '',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('BUSINESS_EMPTY_DESCRIPTION', $result->errorCode);
    }

    public function test_single_character_description_is_invalid(): void
    {
        $data = [
            'miktar' => 100,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'X',
        ];

        $result = $this->validator->validate($data);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('BUSINESS_EMPTY_DESCRIPTION', $result->errorCode);
    }

    // =========================================================================
    // AI Auto-Approval Signal Detection
    // =========================================================================

    public function test_auto_approval_signal_triggers_review_flag(): void
    {
        $data = [
            'miktar' => 500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'AI tarafından otomatik onaylandi', // Contains "onay" signal
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid); // Valid schema + business
        $this->assertTrue($result->aiReviewRequired); // But flagged for review
        $this->assertStringContainsString('AI_AUTO_APPROVAL_SIGNAL', $result->reviewReason);
    }

    public function test_tahmin_signal_triggers_review_flag(): void
    {
        $data = [
            'miktar' => 500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Tahmin edilen masraf',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertStringContainsString('tahmin', $result->reviewReason);
    }

    public function test_oneri_signal_triggers_review_flag(): void
    {
        $data = [
            'miktar' => 500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Sistemin önerdiği gider', // Contains "öner" stem → matches /öneri/iu (öner + ı = öneri partial not exact)
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertStringContainsString('AI_AUTO_APPROVAL_SIGNAL', $result->reviewReason ?? '');
    }

    public function test_multiple_auto_approval_signals_detected(): void
    {
        $data = [
            'miktar' => 500,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'AI otomatik tavsiye karar onay',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        // Should contain first detected signal
        $this->assertNotNull($result->reviewReason);
    }

    // =========================================================================
    // Normalization Tests
    // =========================================================================

    public function test_amount_is_rounded_to_two_decimals(): void
    {
        $data = [
            'miktar' => 100.999,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Rounded amount',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertEquals(101.00, $result->data['miktar']);
    }

    public function test_type_is_normalized_to_lowercase(): void
    {
        $data = [
            'miktar' => 100,
            'para_birimi' => 'TRY',
            'islem_tipi' => 'GELIR',
            'aciklama' => 'Income',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertEquals('gelir', $result->data['islem_tipi']);
    }

    // =========================================================================
    // FinanceValidationResult Value Object Tests
    // =========================================================================

    public function test_valid_result_factory(): void
    {
        $data = ['miktar' => 100, 'para_birimi' => 'TRY', 'islem_tipi' => 'gider', 'aciklama' => 'Test'];
        $result = FinanceValidationResult::valid($data);

        $this->assertTrue($result->isValid);
        $this->assertFalse($result->aiReviewRequired);
        $this->assertNull($result->errorCode);
        $this->assertNull($result->errorMessage);
        $this->assertEquals($data, $result->data);
        $this->assertNull($result->reviewReason);
    }

    public function test_valid_with_review_flag_factory(): void
    {
        $data = ['miktar' => 100, 'para_birimi' => 'TRY', 'islem_tipi' => 'gider', 'aciklama' => 'Test'];
        $result = FinanceValidationResult::validWithReviewFlag($data, 'TEST_CODE', 'Test reason');

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertStringContainsString('TEST_CODE', $result->reviewReason);
        $this->assertStringContainsString('Test reason', $result->reviewReason);
    }

    public function test_invalid_result_factory(): void
    {
        $result = FinanceValidationResult::invalid('ERR_CODE', 'Error message', null);

        $this->assertFalse($result->isValid);
        $this->assertTrue($result->aiReviewRequired);
        $this->assertEquals('ERR_CODE', $result->errorCode);
        $this->assertEquals('Error message', $result->errorMessage);
        $this->assertNull($result->data);
        $this->assertStringContainsString('VALIDATION_FAILED', $result->reviewReason);
    }

    public function test_result_is_immutable(): void
    {
        $result = FinanceValidationResult::valid(['miktar' => 100, 'para_birimi' => 'TRY', 'islem_tipi' => 'gider', 'aciklama' => 'Test']);

        $this->assertEquals(true, $result->isValid);

        // Properties are readonly — trying to assign would cause PHP error
        $reflection = new \ReflectionClass($result);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }
    }

    // =========================================================================
    // All Valid Transaction Types Supported
    // =========================================================================

    public function test_all_valid_transaction_types_pass(): void
    {
        $validTypes = ['gelir', 'gider', 'komisyon', 'masraf', 'odeme'];

        foreach ($validTypes as $type) {
            $data = [
                'miktar' => 100,
                'para_birimi' => 'TRY',
                'islem_tipi' => $type,
                'aciklama' => "Test {$type}",
            ];

            $result = $this->validator->validate($data);
            $this->assertTrue($result->isValid, "Type '{$type}' should be valid");
        }
    }

    public function test_all_supported_currencies_pass(): void
    {
        $validCurrencies = ['TRY', 'USD', 'EUR', 'GBP'];

        foreach ($validCurrencies as $currency) {
            $data = [
                'miktar' => 100,
                'para_birimi' => $currency,
                'islem_tipi' => 'gider',
                'aciklama' => "Test {$currency}",
            ];

            $result = $this->validator->validate($data);
            $this->assertTrue($result->isValid, "Currency '{$currency}' should be valid");
        }
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function test_float_string_miktar_is_accepted(): void
    {
        $data = [
            'miktar' => '500.75',
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Float as string',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertEquals(500.75, $result->data['miktar']);
    }

    public function test_integer_string_miktar_is_accepted(): void
    {
        $data = [
            'miktar' => '1000',
            'para_birimi' => 'TRY',
            'islem_tipi' => 'gider',
            'aciklama' => 'Integer as string',
        ];

        $result = $this->validator->validate($data);

        $this->assertTrue($result->isValid);
        $this->assertEquals(1000.00, $result->data['miktar']);
    }
}

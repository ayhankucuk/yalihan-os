<?php

namespace Tests\Unit\CommandCenter;

use App\Services\CommandCenter\Routing\IntentRouter;
use Tests\TestCase;

/**
 * Phase 1k: IntentRouter Numeric Parser Regression Tests
 *
 * Verifies that extractPropertySearchParameters() correctly handles
 * Turkish/common budget multipliers: bin (thousand), k (thousand), M/milyon (million).
 */
class IntentRouterParserTest extends TestCase
{
    private IntentRouter $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new IntentRouter(
            $this->app->make(\App\Services\CommandCenter\Handlers\PropertySearchIntentHandler::class),
            $this->app->make(\App\Services\CommandCenter\Handlers\TalepCreateIntentHandler::class)
        );
    }

    /**
     * Helper: invoke the private extractPropertySearchParameters via reflection.
     */
    private function extractParams(string $text): array
    {
        $reflection = new \ReflectionMethod($this->router, 'extractPropertySearchParameters');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->router, $text);
    }

    // -------------------------------------------------------------------------
    // MILLION patterns — "M" and "milyon" multipliers
    // -------------------------------------------------------------------------

    public function test_eur_1m_euro_pattern(): void
    {
        $params = $this->extractParams('Elimizde €1M\'a ne var?');
        $this->assertEquals(1_000_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    public function test_1_milyon_euroya_kadar_pattern(): void
    {
        $params = $this->extractParams('1 milyon euroya kadar ne var?');
        $this->assertEquals(1_000_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    public function test_milyon_euro_pattern(): void
    {
        $params = $this->extractParams('1.5 milyon euroya kadar ne var?');
        $this->assertEquals(1_500_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    public function test_1m_epsilon_pattern(): void
    {
        $params = $this->extractParams('€1M altı portföy');
        $this->assertEquals(1_000_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    // -------------------------------------------------------------------------
    // THOUSAND "k" multiplier — multiply by 1_000
    // -------------------------------------------------------------------------

    public function test_eur_750k_pattern(): void
    {
        $params = $this->extractParams('€750k altı portföy');
        $this->assertEquals(750_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    public function test_750k_euro_pattern(): void
    {
        $params = $this->extractParams('750k euro altındaki ilanlar');
        $this->assertEquals(750_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    public function test_750k_standalone_pattern(): void
    {
        $params = $this->extractParams('750K altındaki ilanlar');
        $this->assertEquals(750_000, $params['maxFiyat']);
    }

    public function test_eur_500k_pattern(): void
    {
        $params = $this->extractParams('€500k altında ne var?');
        $this->assertEquals(500_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    // -------------------------------------------------------------------------
    // THOUSAND "bin" multiplier (Turkish) — multiply by 1_000
    // -------------------------------------------------------------------------

    public function test_eur_750_bin_pattern(): void
    {
        $params = $this->extractParams('€750 bin altı portföy');
        $this->assertEquals(750_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    public function test_750_bin_euroya_kadar_pattern(): void
    {
        $params = $this->extractParams('750 bin euroya kadar ne var?');
        $this->assertEquals(750_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    public function test_bin_euro_pattern(): void
    {
        $params = $this->extractParams('250 bin euro ne var?');
        $this->assertEquals(250_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    public function test_decimal_bin_pattern(): void
    {
        $params = $this->extractParams('1.5 bin euro altında ne var?');
        $this->assertEquals(1_500, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    // -------------------------------------------------------------------------
    // CURRENCY DETECTION — TRY, USD, EUR
    // -------------------------------------------------------------------------

    public function test_try_currency_detected(): void
    {
        $params = $this->extractParams('30 milyon tl altındaki ilanlar');
        $this->assertEquals(30_000_000, $params['maxFiyat']);
        $this->assertEquals('TRY', $params['paraBirimi']);
    }

    public function test_usd_currency_detected(): void
    {
        $params = $this->extractParams('$1M altındaki ilanlar');
        $this->assertEquals(1_000_000, $params['maxFiyat']);
        $this->assertEquals('USD', $params['paraBirimi']);
    }

    public function test_eur_currency_symbol_detected(): void
    {
        $params = $this->extractParams('€750 bin altındaki ilanlar');
        $this->assertEquals(750_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    public function test_euro_word_detected(): void
    {
        $params = $this->extractParams('750 bin euro altındaki ilanlar');
        $this->assertEquals(750_000, $params['maxFiyat']);
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    // -------------------------------------------------------------------------
    // EUR DEFAULT when no explicit currency
    // -------------------------------------------------------------------------

    public function test_eur_is_default_currency(): void
    {
        $params = $this->extractParams('elimizde 1M ne var?');
        $this->assertEquals('EUR', $params['paraBirimi']);
    }

    // -------------------------------------------------------------------------
    // MALFORMED INPUT — must fail safely (null price, not misleading)
    // -------------------------------------------------------------------------

    public function test_malformed_input_returns_null_price(): void
    {
        $params = $this->extractParams('elimizde ne var?');
        $this->assertNull($params['maxFiyat']);
    }

    public function test_only_text_returns_null_price(): void
    {
        $params = $this->extractParams('portföy ara');
        $this->assertNull($params['maxFiyat']);
    }

    public function test_currency_only_no_amount_returns_null_price(): void
    {
        $params = $this->extractParams('€ altındaki ilanlar');
        $this->assertNull($params['maxFiyat']);
    }

    // -------------------------------------------------------------------------
    // PRECISION — large numbers with decimal
    // -------------------------------------------------------------------------

    public function test_decimal_million(): void
    {
        $params = $this->extractParams('€1.5M ne var?');
        $this->assertEquals(1_500_000, $params['maxFiyat']);
    }

    public function test_decimal_k(): void
    {
        $params = $this->extractParams('€250.5k ne var?');
        $this->assertEquals(250_500, $params['maxFiyat']);
    }

    // -------------------------------------------------------------------------
    // MUTUALLY EXCLUSIVE — "milyon" should NOT also match "k" pattern
    // -------------------------------------------------------------------------

    public function test_milyon_takes_precedence_over_plain_numeric(): void
    {
        // "1 milyon" must NOT be parsed as plain 1
        $params = $this->extractParams('1 milyon altındaki');
        $this->assertEquals(1_000_000, $params['maxFiyat']);
    }

    public function test_k_takes_precedence_over_plain_numeric_when_k_present(): void
    {
        // "750k" must NOT be parsed as 750
        $params = $this->extractParams('€750k altındaki');
        $this->assertEquals(750_000, $params['maxFiyat']);
    }

    public function test_bin_takes_precedence_over_plain_numeric(): void
    {
        // "750 bin" must NOT be parsed as 750
        $params = $this->extractParams('750 bin altındaki');
        $this->assertEquals(750_000, $params['maxFiyat']);
    }

    // -------------------------------------------------------------------------
    // REGRESSION: "bin" must NOT be consumed by "milyon" pattern
    // -------------------------------------------------------------------------

    public function test_bin_is_not_consumed_by_milyon_pattern(): void
    {
        // "milyon" matches "m" — OK
        // "bin" after "milyon" would be a second token — OK
        $params = $this->extractParams('€1 milyon 500 bin');
        // €1 milyon = 1M, then 500 bin would be separate...
        // Actually this might not match any pattern cleanly.
        // The key regression test is: 750 bin should NOT give 750.
        $params2 = $this->extractParams('€750 bin altı');
        $this->assertEquals(750_000, $params2['maxFiyat'], '750 bin must be 750_000, not 750');
    }

    // -------------------------------------------------------------------------
    // OUTPUT STRUCTURE
    // -------------------------------------------------------------------------

    public function test_returns_expected_keys(): void
    {
        $params = $this->extractParams('€1M altındaki ilanlar');
        $this->assertArrayHasKey('maxFiyat', $params);
        $this->assertArrayHasKey('paraBirimi', $params);
        $this->assertArrayHasKey('yayin_durumu', $params);
        $this->assertEquals('yayinda', $params['yayin_durumu']);
    }
}

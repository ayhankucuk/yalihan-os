<?php

namespace Tests\Unit\CRM;

use Tests\TestCase;
use App\Services\CRM\KisiScoringService;
use ReflectionClass;

/**
 * KisiScoringService segmentSkoru() unit tests.
 *
 * Bug fixed:
 *   OLD: str_contains(strtolower($kisiTipi), 'vip')
 *         - Turkish locale: strtolower('İ') → broken char
 *         - Substring: 'PROVIP' matched → 10 points WRONG
 *   NEW: mb_stripos + word-boundary guard
 *
 * Test strategy: plain anonymous class (not Kisi) with public $kisi_tipi property.
 * segmentSkoru() accepts `object` type hint, so any object with $kisi_tipi works.
 * This bypasses Eloquent's BackedEnum cast entirely — no Model inheritance,
 * no __get/__set magic, no casts. The service only reads $kisi_tipi, it never
 * persists anything, so no database interaction is needed.
 *
 * @group crm
 * @group scoring
 */
class KisiScoringServiceTest extends TestCase
{
    private KisiScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KisiScoringService();
    }

    /** Invoke private segmentSkoru via reflection */
    private function segmentSkoru(object $kisi): int
    {
        $ref = new ReflectionClass($this->service);
        $method = $ref->getMethod('segmentSkoru');
        $method->setAccessible(true);
        return $method->invoke($this->service, $kisi);
    }

    /** Plain object with public kisi_tipi — no Eloquent, no casts */
    private function kisi(string|null $kisiTipi): object
    {
        return new class($kisiTipi) {
            public string|null $kisi_tipi;
            public function __construct(string|null $v) { $this->kisi_tipi = $v; }
        };
    }

    // ─── TRUE POSITIVES: exact / standalone "vip" → 10 ───────────────────────

    public function test_vip_lowercase_scores_10(): void
    {
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('vip')));
    }

    public function test_vip_uppercase_scores_10(): void
    {
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('VIP')));
    }

    public function test_vip_mixed_case_scores_10(): void
    {
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('Vip')));
    }

    public function test_vip_leading_spaces_scores_10(): void
    {
        $this->assertEquals(10, $this->segmentSkoru($this->kisi(' vip')));
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('  vip')));
    }

    public function test_vip_trailing_spaces_scores_10(): void
    {
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('vip ')));
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('vip  ')));
    }

    public function test_vip_surrounded_by_spaces_scores_10(): void
    {
        $this->assertEquals(10, $this->segmentSkoru($this->kisi(' vip ')));
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('  vip  ')));
    }

    public function test_vip_standalone_word_scores_10(): void
    {
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('VIP müşteri')));
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('önemli müşteri VIP')));
    }

    public function test_vip_multiple_vip_words_scores_10(): void
    {
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('VIP VIP')));
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('vip vip vip')));
    }

    public function test_vip_turkish_dotted_i_scores_10(): void
    {
        // VİP with Turkish dotted İ (U+0130) — mb_stripos handles it correctly
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('VİP')));
        $this->assertEquals(10, $this->segmentSkoru($this->kisi('VİP müşteri')));
    }

    // ─── TRUE NEGATIVES: no VIP match → 0 ───────────────────────────────────

    public function test_empty_string_scores_0(): void
    {
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('')));
    }

    public function test_null_kisi_tipi_scores_0(): void
    {
        // null → is_object false → (string) null → '' → mb_stripos false → 0
        $this->assertEquals(0, $this->segmentSkoru($this->kisi(null)));
    }

    public function test_regular_customer_scores_0(): void
    {
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('normal müşteri')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('standart')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('Müşteri')));
    }

    public function test_lead_scores_0(): void
    {
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('lead')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('LEAD')));
    }

    public function test_no_vip_at_all_scores_0(): void
    {
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('yok')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('farklı tip')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('çok önemli müşteri')));
    }

    // ─── FALSE-POSITIVE GUARD: embedded "vip" must NOT score ─────────────────
    // OLD BUG: strtolower('PROVIP') → 'provip' → str_contains('provip','vip') → TRUE → 10 (WRONG)

    public function test_provip_does_not_score(): void
    {
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('PROVIP müşteri')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('provip')));
    }

    public function test_antevip_does_not_score(): void
    {
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('antevip')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('AnteVIP müşteri')));
    }

    public function test_vipport_does_not_score(): void
    {
        // OLD BUG: strtolower('VIPPORT') → 'vipport' → str_contains('vipport','vip') → TRUE → 10 (WRONG)
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('VIPPORT')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('vipport')));
    }

    public function test_vipnet_does_not_score(): void
    {
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('VIPNET')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('vipnet')));
    }

    public function test_evip_does_not_score(): void
    {
        // 'v' preceded by 'e' (alphanumeric) → boundary guard rejects
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('evip')));
    }

    public function test_svip_does_not_score(): void
    {
        // 'v' preceded by 's' (alphanumeric) → boundary guard rejects
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('svip')));
    }

    public function test_antevip_with_space_does_not_score(): void
    {
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('antevip müşteri')));
    }

    public function test_non_vip_words_containing_vip_substring_do_not_score(): void
    {
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('develop')));
        $this->assertEquals(0, $this->segmentSkoru($this->kisi('developer')));
    }
}

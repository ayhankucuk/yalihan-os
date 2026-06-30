<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\PropertyHub\Governance;

use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Modules\GovernanceCore\Services\AutoContainmentPolicy;
use DomainException;
use Tests\TestCase;

/**
 * VersionStateMachine Unit Tests — Context7 DURUM_* API
 *
 * AutoContainmentPolicy mock'lanır — bu bir unit test.
 * Sadece ALLOWED_TRANSITIONS ve DomainException davranışı doğrulanır.
 *
 * Sabitler:
 *   DURUM_TASLAK      = 'TASLAK'
 *   DURUM_INCELEME    = 'INCELEME'
 *   DURUM_ONAYLANDI   = 'ONAYLANDI'
 *   DURUM_AKTIF       = 'AKTIF'
 *   DURUM_ARSIVLENDI  = 'ARSIVLENDI'
 *
 * @group property-hub-governance
 */
class VersionStateMachineTest extends TestCase
{
    private VersionStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();

        // AutoContainmentPolicy'yi mock'la — GovernanceRiskScorer DB bağımlılığını bypass et.
        // Bu bir unit test: VersionStateMachine geçiş mantığını izole ediyoruz.
        $policyMock = \Mockery::mock(AutoContainmentPolicy::class);
        $policyMock->shouldReceive('authorize')->andReturnNull();
        $this->app->instance(AutoContainmentPolicy::class, $policyMock);

        $this->stateMachine = app(VersionStateMachine::class);
    }

    // ─── Geçerli Geçişler ────────────────────────────────────

    /**
     * @dataProvider validTransitionsProvider
     */
    public function test_it_allows_valid_transitions(string $from, string $to): void
    {
        $version = $this->makeVersion($from);

        // assertTransition() DomainException fırlatmamalı
        $this->stateMachine->assertTransition($version, $to);

        $this->assertTrue(true, "Geçiş {$from} → {$to} izin verilmeli.");
    }

    public static function validTransitionsProvider(): array
    {
        return [
            'TASLAK → INCELEME'       => [VersionStateMachine::DURUM_TASLAK,     VersionStateMachine::DURUM_INCELEME],
            'INCELEME → ONAYLANDI'    => [VersionStateMachine::DURUM_INCELEME,    VersionStateMachine::DURUM_ONAYLANDI],
            'ONAYLANDI → AKTIF'       => [VersionStateMachine::DURUM_ONAYLANDI,   VersionStateMachine::DURUM_AKTIF],
            'ONAYLANDI → ARSIVLENDI'  => [VersionStateMachine::DURUM_ONAYLANDI,   VersionStateMachine::DURUM_ARSIVLENDI],
            'AKTIF → ARSIVLENDI'      => [VersionStateMachine::DURUM_AKTIF,       VersionStateMachine::DURUM_ARSIVLENDI],
        ];
    }

    // ─── Geçersiz Geçişler ───────────────────────────────────

    /**
     * @dataProvider invalidTransitionsProvider
     */
    public function test_it_throws_on_invalid_transition(string $from, string $to): void
    {
        $this->expectException(DomainException::class);

        $version = $this->makeVersion($from);
        $this->stateMachine->assertTransition($version, $to);
    }

    public static function invalidTransitionsProvider(): array
    {
        return [
            'TASLAK → AKTIF (atlama)'          => [VersionStateMachine::DURUM_TASLAK,    VersionStateMachine::DURUM_AKTIF],
            'TASLAK → ONAYLANDI (atlama)'       => [VersionStateMachine::DURUM_TASLAK,    VersionStateMachine::DURUM_ONAYLANDI],
            'INCELEME → AKTIF (atlama)'         => [VersionStateMachine::DURUM_INCELEME,  VersionStateMachine::DURUM_AKTIF],
            'AKTIF → TASLAK (geri dönüş)'       => [VersionStateMachine::DURUM_AKTIF,     VersionStateMachine::DURUM_TASLAK],
            'AKTIF → INCELEME (geri dönüş)'     => [VersionStateMachine::DURUM_AKTIF,     VersionStateMachine::DURUM_INCELEME],
            'AKTIF → ONAYLANDI (geri dönüş)'    => [VersionStateMachine::DURUM_AKTIF,     VersionStateMachine::DURUM_ONAYLANDI],
        ];
    }

    // ─── Terminal State ──────────────────────────────────────

    /**
     * ARSIVLENDI terminal state — hiçbir geçişe izin verilmez.
     *
     * @dataProvider archivedTerminalProvider
     */
    public function test_archived_is_terminal(string $targetDurum): void
    {
        $this->expectException(DomainException::class);

        $version = $this->makeVersion(VersionStateMachine::DURUM_ARSIVLENDI);
        $this->stateMachine->assertTransition($version, $targetDurum);
    }

    public static function archivedTerminalProvider(): array
    {
        return [
            'ARSIVLENDI → TASLAK'    => [VersionStateMachine::DURUM_TASLAK],
            'ARSIVLENDI → INCELEME'  => [VersionStateMachine::DURUM_INCELEME],
            'ARSIVLENDI → ONAYLANDI' => [VersionStateMachine::DURUM_ONAYLANDI],
            'ARSIVLENDI → AKTIF'     => [VersionStateMachine::DURUM_AKTIF],
        ];
    }

    // ─── Self Transition ─────────────────────────────────────

    /**
     * canTransition() self = true (VersionStateMachine satır 35-36).
     * assertTransition() bu yüzden exception fırlatmaz.
     *
     * @dataProvider selfTransitionProvider
     */
    public function test_self_transition_is_allowed(string $durum): void
    {
        $version = $this->makeVersion($durum);

        // canTransition'da from === to → true, DomainException beklenmez
        $this->stateMachine->assertTransition($version, $durum);

        $this->assertTrue(true, "{$durum} → {$durum} self-transition izinli.");
    }

    public static function selfTransitionProvider(): array
    {
        return [
            'TASLAK self'     => [VersionStateMachine::DURUM_TASLAK],
            'INCELEME self'   => [VersionStateMachine::DURUM_INCELEME],
            'ONAYLANDI self'  => [VersionStateMachine::DURUM_ONAYLANDI],
            'AKTIF self'      => [VersionStateMachine::DURUM_AKTIF],
        ];
    }

    // ─── canTransition() Doğrudan ────────────────────────────

    public function test_can_transition_returns_true_for_valid(): void
    {
        $this->assertTrue($this->stateMachine->canTransition(
            VersionStateMachine::DURUM_TASLAK,
            VersionStateMachine::DURUM_INCELEME
        ));
    }

    public function test_can_transition_returns_false_for_invalid(): void
    {
        $this->assertFalse($this->stateMachine->canTransition(
            VersionStateMachine::DURUM_ARSIVLENDI,
            VersionStateMachine::DURUM_AKTIF
        ));
    }

    // ─── Sabit Değer Doğrulama ───────────────────────────────

    public function test_durum_constants_have_correct_values(): void
    {
        $this->assertSame('TASLAK',     VersionStateMachine::DURUM_TASLAK);
        $this->assertSame('INCELEME',   VersionStateMachine::DURUM_INCELEME);
        $this->assertSame('ONAYLANDI',  VersionStateMachine::DURUM_ONAYLANDI);
        $this->assertSame('AKTIF',      VersionStateMachine::DURUM_AKTIF);
        $this->assertSame('ARSIVLENDI', VersionStateMachine::DURUM_ARSIVLENDI);
    }

    // ─── Yardımcı ────────────────────────────────────────────

    /**
     * assertTransition() sadece $version->yonetim_durumu okuyor.
     * id=null patlaması AutoContainmentPolicy'den geliyordu — artık mock'lu.
     */
    private function makeVersion(string $durum): PropertyConfigVersion
    {
        $version = new PropertyConfigVersion();
        $version->yonetim_durumu = $durum;
        return $version;
    }
}

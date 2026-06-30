<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Core;

use DomainException;

/**
 * Version State Machine
 *
 * Enforces business rules for PropertyConfigVersion state transitions.
 */
class VersionStateMachine
{
    public const DURUM_TASLAK = 'TASLAK';
    public const DURUM_INCELEME = 'INCELEME';
    public const DURUM_ONAYLANDI = 'ONAYLANDI';
    public const DURUM_AKTIF = 'AKTIF';
    public const DURUM_ARSIVLENDI = 'ARSIVLENDI';

    private const ALLOWED_TRANSITIONS = [
        self::DURUM_TASLAK => [self::DURUM_INCELEME],
        self::DURUM_INCELEME => [self::DURUM_ONAYLANDI],
        self::DURUM_ONAYLANDI => [self::DURUM_AKTIF, self::DURUM_ARSIVLENDI],
        self::DURUM_AKTIF => [self::DURUM_ARSIVLENDI],
        self::DURUM_ARSIVLENDI => [], // Terminal state
    ];

    /**
     * Check if a transition is valid.
     */
    public function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::ALLOWED_TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Assert that a transition is valid, throw exception otherwise.
     *
     * @throws \DomainException
     */
    public function assertTransition(\App\Models\PropertyConfigVersion $version, string $to): void
    {
        $from = $version->yonetim_durumu;

        if (!$this->canTransition($from, $to)) {
            throw new \DomainException("Yalıhan Governance Error: Invalid state transition from {$from} to {$to}");
        }

        // 🚨 SPRINT 14: Predictive Governance Check
        // We use resolver to avoid circular dependency or messy constructor in SM
        $policy = resolve(\App\Modules\GovernanceCore\Services\AutoContainmentPolicy::class);
        $policy->authorize($version, $to);
    }
}

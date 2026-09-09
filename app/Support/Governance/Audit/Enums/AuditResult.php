<?php

namespace App\Support\Governance\Audit\Enums;

/**
 * Standardized audit check result status.
 *
 * Used across all audit commands for consistent reporting.
 */
enum AuditResult: string
{
    case PASS  = 'pass';
    case FAIL  = 'fail';
    case WARN  = 'warn';
    case SKIP  = 'skip';

    /**
     * Human-readable icon for terminal output.
     */
    public function icon(): string
    {
        return match($this) {
            self::PASS => '✅',
            self::FAIL => '❌',
            self::WARN => '⚠️',
            self::SKIP => '⏭️',
        };
    }

    /**
     * Exit code mapping per contract.
     * 0 = pass, 1 = fail, 0 = warn/skip (warnings don't fail CI by default)
     */
    public function exitCode(): int
    {
        return match($this) {
            self::PASS => 0,
            self::FAIL => 1,
            self::WARN => 0,
            self::SKIP => 0,
        };
    }

    /**
     * Convert from legacy Turkish status strings.
     */
    public static function fromLegacy(string $status): self
    {
        return match(strtolower($status)) {
            'basarili', 'success', 'passed', 'clean' => self::PASS,
            'hata', 'error', 'failed', 'has_drift', 'blocker' => self::FAIL,
            'uyari', 'warning', 'warn' => self::WARN,
            'atlandi', 'skipped', 'skip', 'n/a' => self::SKIP,
            default => self::FAIL,
        };
    }
}

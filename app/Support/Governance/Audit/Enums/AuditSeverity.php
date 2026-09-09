<?php

namespace App\Support\Governance\Audit\Enums;

/**
 * Audit finding severity levels.
 * Used to determine exit codes and CI failure behavior.
 */
enum AuditSeverity: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case INFO = 'info';

    /**
     * Priority rank for sorting (higher = more severe).
     */
    public function rank(): int
    {
        return match($this) {
            self::CRITICAL => 5,
            self::HIGH => 4,
            self::MEDIUM => 3,
            self::LOW => 2,
            self::INFO => 1,
        };
    }

    /**
     * Exit code when this severity level fails.
     * Critical and High always fail CI; Medium and below are warnings by default.
     */
    public function exitCodeOnFail(): int
    {
        return match($this) {
            self::CRITICAL, self::HIGH => 1,
            default => 0,
        };
    }

    /**
     * Color code for terminal output.
     */
    public function color(): string
    {
        return match($this) {
            self::CRITICAL => 'red',
            self::HIGH => 'magenta',
            self::MEDIUM => 'yellow',
            self::LOW => 'cyan',
            self::INFO => 'gray',
        };
    }

    /**
     * Icon for terminal output.
     */
    public function icon(): string
    {
        return match($this) {
            self::CRITICAL => '🔴',
            self::HIGH => '🟠',
            self::MEDIUM => '🟡',
            self::LOW => '⚪',
            self::INFO => '🔵',
        };
    }

    /**
     * Convert from legacy severity strings.
     */
    public static function fromLegacy(string $severity): self
    {
        return match(strtolower($severity)) {
            'critical', 'crit', 'fatal' => self::CRITICAL,
            'high', 'major' => self::HIGH,
            'medium', 'moderate', 'med' => self::MEDIUM,
            'low', 'minor' => self::LOW,
            'info', 'information', 'debug' => self::INFO,
            default => self::MEDIUM,
        };
    }
}

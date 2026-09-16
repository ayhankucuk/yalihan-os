<?php

namespace App\Support\Governance\Audit\Enums;

/**
 * Evidence label taxonomy for audit provenance.
 *
 * Indicates the level of verification applied to audit findings.
 * Used to distinguish between repo-only checks and runtime/production verified checks.
 */
enum EvidenceLabel: string
{
    /**
     * Code/repo static analysis passed.
     */
    case REPO_VERIFIED = 'REPO_VERIFIED';

    /**
     * Automated tests provide verification evidence.
     */
    case TEST_VERIFIED = 'TEST_VERIFIED';

    /**
     * Local SQLite/MySQL runtime/schema verified.
     */
    case LOCAL_RUNTIME_VERIFIED = 'LOCAL_RUNTIME_VERIFIED';

    /**
     * Live production evidence captured.
     */
    case PRODUCTION_VERIFIED = 'PRODUCTION_VERIFIED';

    /**
     * Conclusion inferred from indirect evidence.
     */
    case INFERRED = 'INFERRED';

    /**
     * Unable to determine verification level.
     */
    case UNKNOWN = 'UNKNOWN';

    /**
     * Blocker found; cannot proceed.
     */
    case BLOCKED_NEEDS_FIX = 'BLOCKED_NEEDS_FIX';

    /**
     * Human review required before determination.
     */
    case NEEDS_REVIEW = 'NEEDS_REVIEW';

    /**
     * High confidence in finding.
     */
    case CONFIDENCE_HIGH = 'CONFIDENCE_HIGH';

    /**
     * Medium confidence in finding.
     */
    case CONFIDENCE_MEDIUM = 'CONFIDENCE_MEDIUM';

    /**
     * Low confidence in finding.
     */
    case CONFIDENCE_LOW = 'CONFIDENCE_LOW';

    /**
     * Whether this label represents a verified state (not a blocker).
     */
    public function isVerified(): bool
    {
        return match($this) {
            self::REPO_VERIFIED,
            self::TEST_VERIFIED,
            self::LOCAL_RUNTIME_VERIFIED,
            self::PRODUCTION_VERIFIED,
            self::CONFIDENCE_HIGH,
            self::CONFIDENCE_MEDIUM,
            self::CONFIDENCE_LOW => true,
            default => false,
        };
    }

    /**
     * Whether this label represents a blocking condition.
     */
    public function isBlocking(): bool
    {
        return match($this) {
            self::BLOCKED_NEEDS_FIX => true,
            default => false,
        };
    }

    /**
     * Color code for terminal output.
     */
    public function color(): string
    {
        return match($this) {
            self::REPO_VERIFIED, self::TEST_VERIFIED => 'green',
            self::LOCAL_RUNTIME_VERIFIED => 'cyan',
            self::PRODUCTION_VERIFIED => 'blue',
            self::INFERRED => 'yellow',
            self::BLOCKED_NEEDS_FIX => 'red',
            self::NEEDS_REVIEW => 'magenta',
            default => 'gray',
        };
    }
}

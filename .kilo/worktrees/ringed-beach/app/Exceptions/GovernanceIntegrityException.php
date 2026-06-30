<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * GovernanceIntegrityException
 *
 * Phase 14 — Hash Chain kırılması tespit edildiğinde fırlatılır.
 * SAB Core Constitution v2.6 — Fail-Loud: sessiz kalmaz.
 */
class GovernanceIntegrityException extends RuntimeException
{
    public function __construct(string $message, private readonly int $decisionId = 0)
    {
        parent::__construct($message);
    }

    public function getDecisionId(): int
    {
        return $this->decisionId;
    }
}

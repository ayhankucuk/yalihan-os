<?php

declare(strict_types=1);

namespace App\Exceptions\PropertyHub;

use App\Exceptions\CriticalGovernanceException;

/**
 * Exception thrown when an ACTIVE configuration version is targeted for illegal mutation.
 * Part of the Context7 Zero-Trust Hardening protocol.
 */
class ActiveMutationViolationException extends CriticalGovernanceException
{
    public function __construct(string $message = "ZERO-TRUST VIOLATION: Active configuration cannot be mutated directly.", int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

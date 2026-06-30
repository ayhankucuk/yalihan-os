<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Critical Governance Exception
 *
 * Thrown when the Yalıhan Governance Layer detects a critical integrity breach.
 */
class CriticalGovernanceException extends RuntimeException
{
    //
}

<?php

namespace App\Application\Shared\Exceptions;

use Exception;

/**
 * 🛡️ Governance Exception
 * Thrown when an AI operation is attempted without a valid tenant context.
 */
class TenantContextMissingException extends Exception
{
    protected $message = 'AI_OPERATION_FORBIDDEN: Tenant context is missing or invalid.';
}

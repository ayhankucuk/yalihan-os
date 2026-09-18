<?php

namespace App\Exceptions;

use Exception;

/**
 * CountryOwnershipUnresolvableException
 *
 * Thrown when canonical country ownership cannot be resolved for a N8n webhook flow.
 *
 * This is an intentional fail-closed exception. When country ownership cannot
 * be determined from canonical business entities, the system MUST NOT silently
 * write a NULL and continue — doing so creates orphaned records that bypass
 * CountryScope query isolation.
 *
 * Usage:
 *   - mesajTaslagi flow (Communication has no ulke_id column)
 *   - Any flow where both primary and fallback resolvers return null
 *
 * Resolution path:
 *   - For mesajTaslagi: separate task required to add ulke_id to communications table
 *   - For other flows: ensure the referenced business entity has a valid ulke_id
 */
class CountryOwnershipUnresolvableException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception for observability.
     * This exception is always logged at ERROR level before throwing.
     */
    public function report(): void
    {
        // Already logged in CountryOwnershipResolver before throwing.
    }
}

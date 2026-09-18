<?php

namespace App\Exceptions;

use Exception;

/**
 * TenantOwnershipUnresolvableException
 *
 * Thrown when canonical tenant ownership cannot be resolved for an N8n AI draft.
 * This is the intentional fail-closed behaviour — N8n-created AI records MUST
 * carry a resolvable, authoritative tenant_id from canonical domain entities.
 *
 * @see TenantOwnershipResolver
 */
class TenantOwnershipUnresolvableException extends Exception
{
}

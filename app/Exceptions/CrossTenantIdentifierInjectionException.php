<?php

namespace App\Exceptions;

/**
 * CrossTenantIdentifierInjectionException
 *
 * Thrown when caller-supplied business identifiers resolve to different tenants,
 * indicating a cross-tenant identifier injection attempt.
 *
 * This is a SECURITY exception — never expose entity IDs or tenant IDs in the
 * message. Log internally with full context; return a generic security error to caller.
 *
 * @see TenantOwnershipResolver
 */
class CrossTenantIdentifierInjectionException extends TenantOwnershipUnresolvableException
{
}

<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * TenantNotFoundException
 *
 * Thrown when a tenant context cannot be resolved.
 */
class TenantNotFoundException extends Exception
{
    /**
     * Report the exception.
     */
    public function report(): void
    {
        Log::warning('TenantNotFoundException reported: ' . $this->getMessage());
    }
}

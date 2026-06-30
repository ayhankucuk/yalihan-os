<?php

namespace App\Domain\CQRS\Exceptions;

use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * Class CrossTenantAccessException
 *
 * SAB Fail-Loud Exception for illegal multi-tenant access attempts.
 *
 * @package App\Domain\CQRS\Exceptions
 */
class CrossTenantAccessException extends RuntimeException
{
    /**
     * Report the exception for forensic analysis.
     *
     * @return void
     */
    public function report(): void
    {
        Log::critical('CROSS-TENANT ACCESS VIOLATION DETECTED: ' . $this->getMessage(), [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'trace' => $this->getTraceAsString(),
        ]);
    }
}

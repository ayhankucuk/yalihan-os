<?php

namespace App\Exceptions\Governance;

use Exception;

/**
 * Class GlobalHardlockException
 * @package App\Exceptions\Governance
 * @description Exception thrown when any core file checksum mismatch or tenant/system hardlock is triggered.
 */
class GlobalHardlockException extends Exception
{
    /**
     * Render the exception as an HTTP 503 response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => '🚨 SAB SYSTEM HARDLOCK: The platform is currently in a safe/frozen state due to a critical security incident or file tampering attempt.',
                'error_code' => 'GLOBAL_HARDLOCK_ACTIVE'
            ], 503);
        }

        return response()->view('errors.503', [
            'exception' => $this,
            'message' => '🚨 SAB SYSTEM HARDLOCK: System/Tenant is compromised.'
        ], 503);
    }
}

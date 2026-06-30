<?php

namespace App\Support\Guards;

use RuntimeException;

/**
 * Class ConfigGuard
 * 
 * Enforces architectural and security rules for application configuration.
 * Implements a fail-fast strategy during the bootstrap process.
 */
class ConfigGuard
{
    /**
     * Validate all critical configurations.
     * 
     * @throws RuntimeException
     */
    public static function validate(): void
    {
        self::validateOllama();
        
        // Future extensions:
        // self::validateDatabase();
        // self::validateQueue();
    }

    /**
     * Enforce security rules for Ollama API.
     * 
     * @throws RuntimeException
     */
    private static function validateOllama(): void
    {
        $url = config('ai.ollama.url');
        $enforceTls = config('ai.ollama.enforce_tls');

        if (!$url) {
            throw new RuntimeException('GOVERNANCE_VIOLATION: OLLAMA_API_URL is not defined in config/ai.php');
        }

        // Rule: If TLS enforcement is enabled, URL must be HTTPS.
        // In CI (testing), we control this via OLLAMA_ENFORCE_TLS=false in .env
        if ($enforceTls && !str_starts_with($url, 'https://')) {
            throw new RuntimeException(
                'SECURITY_VIOLATION: Ollama API must use HTTPS when TLS enforcement is enabled. ' .
                'Check OLLAMA_ENFORCE_TLS in your environment settings.'
            );
        }
    }
}

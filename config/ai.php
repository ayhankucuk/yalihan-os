<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ollama Service Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for Ollama AI service to prevent drift and 
    | enforce security standards (TLS) across all environments.
    |
    */
    'ollama' => [
        // null default = Ollama not configured in this environment → ConfigGuard skips validation.
        // When OLLAMA_API_URL is explicitly set, TLS enforcement applies.
        'url' => env('OLLAMA_API_URL'),
        'enforce_tls' => env('OLLAMA_ENFORCE_TLS', true),
    ],
];

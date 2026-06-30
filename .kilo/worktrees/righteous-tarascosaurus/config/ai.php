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
        'url' => env('OLLAMA_API_URL', 'http://localhost:11434'),
        'enforce_tls' => env('OLLAMA_ENFORCE_TLS', true),
    ],
];

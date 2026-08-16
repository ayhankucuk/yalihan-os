<?php

/**
 * Guest Concierge — Pilot + LLM Configuration
 *
 * MICRO PILOT READINESS SPRINT
 * PILOT-GATE-01: Pilot allowlist (from env)
 * PILOT-GATE-02: LLM provider configuration
 *
 * Usage:
 *   GUEST_CONCIERGE_ENABLED=true
 *   GUEST_CONCIERGE_KILL_SWITCH=false
 *   GUEST_CONCIERGE_PILOT_TENANT_IDS=1,3
 *   GUEST_CONCIERGE_PILOT_RESERVATION_IDS=1001,1002
 *   CONCIERGE_LLM_PROVIDER=ollama|deepseek|openai
 *   CONCIERGE_LLM_MODEL=deepseek-chat
 *   CONCIERGE_LLM_BASE_URL=http://localhost:11434
 *   CONCIERGE_LLM_API_KEY=
 *   CONCIERGE_LLM_TIMEOUT=30
 */

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Pilot Mode Allowlist — PILOT-GATE-01
    |--------------------------------------------------------------------------
    |
    | PILOT-GATE-01 INVARIANT: Empty allowlist = fail-closed.
    | enabled=true + empty allowlist = FULL ROLLOUT DEĞIL, tam blokaj.
    |
    | env('GUEST_CONCIERGE_PILOT_TENANT_IDS', '')    → '1,3,7'
    | env('GUEST_CONCIERGE_PILOT_RESERVATION_IDS', '') → '1001,1002'
    |
    */

    'pilot' => [
        'tenant_ids' => array_values(array_filter(
            array_map('intval', preg_split('/,/', (string) env('GUEST_CONCIERGE_PILOT_TENANT_IDS', ''), -1, PREG_SPLIT_NO_EMPTY)),
            fn(int $id): bool => $id > 0,
        )),
        'reservation_ids' => array_values(array_filter(
            array_map('intval', preg_split('/,/', (string) env('GUEST_CONCIERGE_PILOT_RESERVATION_IDS', ''), -1, PREG_SPLIT_NO_EMPTY)),
            fn(int $id): bool => $id > 0,
        )),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Provider — PILOT-GATE-02
    |--------------------------------------------------------------------------
    |
    | PILOT-GATE-02: Concierge LLM configuration.
    | Provider selected at runtime via CONCIERGE_LLM_PROVIDER env var.
    |
    | Supported providers:
    |   ollama   — Local Ollama (http://localhost:11434)
    |   deepseek — DeepSeek API
    |   openai  — OpenAI compatible API
    |
    | Hermes architectural role does NOT change — provider is only an
    | implementation detail. Authorization remains in application layer.
    |
    */

    'llm' => [
        'provider' => env('CONCIERGE_LLM_PROVIDER', 'ollama'),

        // Ollama
        'ollama' => [
            'model' => env('CONCIERGE_LLM_OLLAMA_MODEL', 'llama3.2'),
            'base_url' => env('CONCIERGE_LLM_OLLAMA_URL', 'http://localhost:11434'),
            'timeout' => (int) env('CONCIERGE_LLM_TIMEOUT', 30),
        ],

        // DeepSeek
        'deepseek' => [
            'model' => env('CONCIERGE_LLM_DEEPSEEK_MODEL', 'deepseek-chat'),
            'base_url' => env('CONCIERGE_LLM_DEEPSEEK_URL', 'https://api.deepseek.com'),
            'api_key' => env('CONCIERGE_LLM_DEEPSEEK_KEY', ''),
            'timeout' => (int) env('CONCIERGE_LLM_TIMEOUT', 30),
        ],

        // OpenAI compatible
        'openai' => [
            'model' => env('CONCIERGE_LLM_OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('CONCIERGE_LLM_OPENAI_URL', 'https://api.openai.com/v1'),
            'api_key' => env('CONCIERGE_LLM_OPENAI_KEY', ''),
            'timeout' => (int) env('CONCIERGE_LLM_TIMEOUT', 30),
        ],
    ],

];

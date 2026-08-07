<?php

/**
 * Finance Agent Configuration
 *
 * EX-002 Finance Agent — WAVE 2
 *
 * Tüm değerler .env üzerinden değil, bu config dosyasından okunur.
 * app/ içinde env() çağrısı yasak — config() kullan.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Global Kill Switch
    |--------------------------------------------------------------------------
    | Finance Agent'ı tamamen açar veya kapatır.
    | Production'da false ile başla, pilot onaylandıktan sonra aç.
    */
    'enabled' => (bool) env('FINANCE_AGENT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Pilot Mode
    |--------------------------------------------------------------------------
    | strict_mode: true → yalnızca allowlist'teki tenant'lar için aktif.
    | tenants: pilot için izin verilen tenant ID'leri.
    */
    'pilot' => [
        'strict_mode' => (bool) env('FINANCE_AGENT_PILOT_STRICT', true),
        'tenants'     => array_filter(
            array_map('intval', explode(',', env('FINANCE_AGENT_PILOT_TENANTS', '')))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Settings
    |--------------------------------------------------------------------------
    */
    'import' => [
        'enabled' => (bool) env('FINANCE_AGENT_IMPORT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reconciliation Settings
    |--------------------------------------------------------------------------
    | auto_reconcile: import sonrası otomatik reconciliation başlatır.
    | Production'da false bırak — manuel onay akışı için.
    */
    'reconciliation' => [
        'auto_reconcile' => (bool) env('FINANCE_AGENT_AUTO_RECONCILE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Owner Payout Settings
    |--------------------------------------------------------------------------
    */
    'owner_payout' => [
        'enabled' => (bool) env('FINANCE_AGENT_OWNER_PAYOUT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Commission Settings
    |--------------------------------------------------------------------------
    | YALIHAN'ın Airbnb rezervasyonlarından aldığı varsayılan komisyon oranı.
    */
    'commission' => [
        'default_rate' => (float) env('FINANCE_AGENT_DEFAULT_COMMISSION_RATE', 10.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Approval Settings
    |--------------------------------------------------------------------------
    | required: true → tüm payout'lar admin onayı gerektirir (önerilen).
    */
    'approval' => [
        'required' => (bool) env('FINANCE_AGENT_APPROVAL_REQUIRED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'enabled'         => (bool) env('FINANCE_AGENT_RETRY_ENABLED', true),
        'max_attempts'    => (int) env('FINANCE_AGENT_RETRY_MAX_ATTEMPTS', 3),
        'backoff_seconds' => (int) env('FINANCE_AGENT_RETRY_BACKOFF', 60),
    ],

];

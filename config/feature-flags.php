<?php

/**
 * Feature Flags — Runtime davranış karantina anahtarları.
 *
 * Kural (SAB Rule 6): Legacy üretim hattı ölçümsüz çalışamaz.
 * - Default: kapalı
 * - Açılırsa: telemetry + allowlist zorunlu
 *
 * @see docs/adr/2026-02-22-legacy-generator-quarantine.md
 *
 * Sprint 12C: ListingCrud v2 Feature Flags
 * - listing_crud_v2_enabled: ListingCrudService kullanımını açar
 * - listing_crud_v2_shadow: Her iki servisi de çalıştırır, legacy sonucu döner
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Legacy JSON Generator (PropertyTemplateGeneratorService)
    |--------------------------------------------------------------------------
    */
    'legacy_generator_enabled' => (bool) env('APP_LEGACY_GENERATOR_ENABLED', false),
    'legacy_generator_allowlist' => [
        'tenant_ids' => [],
        'routes'     => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sprint 12C: ListingCrud v2 Feature Flags
    |--------------------------------------------------------------------------
    | listing_crud_v2_enabled: ListingCrudService'e geçişi açar
    |   - false (default): IlanCrudService kullanılır
    |   - true: ListingCrudService kullanılır
    |
    | listing_crud_v2_shadow: Paralel çalıştırma modu
    |   - false: Tek servis çalışır
    |   - true: Her iki servis de çalışır, legacy sonucu döner
    |   - Kullanım: Parity validation için
    |
    | listing_crud_v2_allowlist: Tenant/route bazlı geçiş
    |   - Boşsa: Tüm istekler etkilenir
    |   - Doluysa: Sadece eşleşenler etkilenir
    */
    'listing_crud_v2_enabled' => (bool) env('LISTING_CRUD_V2_ENABLED', false),
    'listing_crud_v2_shadow' => (bool) env('LISTING_CRUD_V2_SHADOW', false),

    'listing_crud_v2_allowlist' => [
        'tenant_ids' => [],
        'routes'     => [],
    ],

];

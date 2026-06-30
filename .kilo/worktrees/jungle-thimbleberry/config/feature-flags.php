<?php

/**
 * Feature Flags — Runtime davranış karantina anahtarları.
 *
 * Kural (SAB Rule 6): Legacy üretim hattı ölçümsüz çalışamaz.
 * - Default: kapalı
 * - Açılırsa: telemetry + allowlist zorunlu
 *
 * @see docs/adr/2026-02-22-legacy-generator-quarantine.md
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Legacy JSON Generator (PropertyTemplateGeneratorService)
    |--------------------------------------------------------------------------
    | Default: false (karantina)
    | Açmak için: APP_LEGACY_GENERATOR_ENABLED=true + allowlist tenant/route
    |
    | Ölçüm metrikleri:
    |   - legacy_generator_call_count
    |   - legacy_generator_success_count
    |   - legacy_generator_fail_count
    |   - fallback_trigger_count (hedef: 0)
    |
    | Hard cut planı: 7–14 günlük usage grafiği 0'a inince uygula.
    */
    'legacy_generator_enabled' => (bool) env('APP_LEGACY_GENERATOR_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Legacy Generator Allowlist (tenant / route bazlı)
    |--------------------------------------------------------------------------
    | legacy_generator_enabled=true olsa bile bu liste boşsa tüm istekler geçer.
    | Doluysa yalnızca eşleşen tenant/route allowlist edilir.
    |
    | Format:
    |   ['tenant_ids' => [1, 2], 'routes' => ['admin.property-hub.ai-generate']]
    */
    'legacy_generator_allowlist' => [
        'tenant_ids' => [],
        'routes'     => [],
    ],

];

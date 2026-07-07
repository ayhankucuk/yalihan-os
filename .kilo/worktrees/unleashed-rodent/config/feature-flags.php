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

    /*
    |--------------------------------------------------------------------------
    | Property Workspace Foundation (Sprint 6.0)
    |--------------------------------------------------------------------------
    | Default: false (kapalı — Sprint 6.0 omurga geliştirme aşamasında)
    | Açmak için: APP_PROPERTY_WORKSPACE_ENABLED=true
    |
    | Bu flag:
    | - Workspace oluşturma akışını açar/kapar
    | - Dashboard kartını görünür kılar
    | - Intent Selection UI'yi aktif eder
    |
    | Rollback: php artisan feature:disable property_workspace_v1
    |
    | Kapatıldığında: Mevcut tüm akışlar çalışmaya devam eder.
    | Açıldığında: WorkspaceRuntime → Timeline → Dashboard entegre olur.
    |
    | Sprint 6.0 Exit Criteria:
    |   1. Workspace oluşturuldu (UUID döndü)
    |   2. Intent çalıştı (Template yüklendi)
    |   3. Template çalıştı (Required fields oluştu)
    |   4. State machine: created→draft geçti
    |   5. Timeline: Event yazıldı
    |   6. Tenant: Tenant test PASS
    */
    'property_workspace_v1' => (bool) env('APP_PROPERTY_WORKSPACE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Property Workspace Allowlist (tenant / route bazlı)
    |--------------------------------------------------------------------------
    | property_workspace_v1=true olsa bile bu liste boşsa tüm istekler geçer.
    | Doluysa yalnızca eşleşen tenant/route allowlist edilir.
    */
    'property_workspace_v1_allowlist' => [
        'tenant_ids' => [],
        'routes'     => [
            'admin.workspace.create',
            'admin.workspace.show',
            'admin.workspace.index',
            'admin.workspace.intent',
            'admin.workspace.template',
            'admin.workspace.state',
        ],
    ],

];

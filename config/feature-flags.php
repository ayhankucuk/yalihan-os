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
    | WhatsApp Notification Pilot (N1-B Stable — 2026-08-07)
    |--------------------------------------------------------------------------
    | Default: false (karantina — SAB Rule 6)
    | Açmak için: PILOT_NOTIFICATION_GLOBAL=true + pilot_allowlist dolu olmalı
    |
    | Ölçüm metrikleri (pilot başarı kriterleri):
    |   - gonderim_suresi       ≤ 60 saniye
    |   - external_message_id    oluştu
    |   - delivery_audit_id      oluştu
    |   - kill_switch_test       gönderim durdu
    |
    | 155 hata: Certification debt — EX-001 sonrası temizlenecek
    |   Özellikle setEventDispatcher(null) grubu Airbnb test altyapısıyla ilişkili
    */
    'whatsapp_pilot_global' => (bool) env('PILOT_NOTIFICATION_GLOBAL', false),

    /*
    |--------------------------------------------------------------------------
    | Pilot Allowlist — Tenant + Property ID Bazlı
    |--------------------------------------------------------------------------
    | whatsapp_pilot_global=true olsa bile sadece bu listedeki tenant/property
    | ikilileri için bildirim gönderilir.
    |
    | Format: tenant_id => [property_id, ...]
    | Boş = hiçbir tenant pilot'a dahil değil (güvenlik)
    */
    'pilot_notification_allowlist' => [
        'tenant_ids' => [],   // tenant ID'ler
        'property_ids' => [], // property ID'ler (opsiyonel — boşsa tüm property'ler)
        // Örnek:
        // 'tenant_ids' => [1, 5],
        // 'property_ids' => [42, 88],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Kill Switch
    |--------------------------------------------------------------------------
    | true = tüm notification gönderimleri durdurulur
    | false = normal akış (allowlist'e tabi)
    |
    | Operasyonel kullanım: acil durdurma, staging test, pilot doğrulama
    */
    'notification_kill_switch' => (bool) env('NOTIFICATION_KILL_SWITCH', false),

    /*
    |--------------------------------------------------------------------------
    | Guest Concierge — AI Guest Communication (GUEST_CONCIERGE Phase 1)
    |--------------------------------------------------------------------------
    | GUEST_CONCIERGE Phase 1 — SAAB Session 134
    |
    | guest_concierge_enabled:
    |   Default: false (karantina — SAB Rule 6)
    |   Açmak için: GUEST_CONCIERGE_ENABLED=true + pilot_allowlist dolu olmalı
    |
    | guest_concierge_kill_switch:
    |   true = tüm concierge mesajları durdurulur (acil durdurma)
    |   false = normal akış
    |
    | guest_concierge_pilot_allowlist:
    |   tenant_ids boş = hiçbir tenant concierge'e dahil değil
    |   tenant_ids = [1] = sadece tenant 1 concierge kullanır
    |   Pilot: 1 tenant, 1 reservation ile başla
    |
    | Phase 1 Kapsam (7 intent):
    |   AUTO_ANSWER: WIFI_INFO, CHECK_IN_TIME, CHECK_OUT_TIME, PARKING_INFO, HOUSE_RULES
    |   AUTO_ACTION: TECHNICAL_ISSUE → Gorev, CLEANING_REQUEST → Gorev
    |   ESCALATE: Tüm diğer intentler
    |
    | Güvenlik Garantileri (SAAB GC-D1...GC-D12):
    |   - AI hiçbir zaman doğrudan aksiyon yetkisi almaz
    |   - Kapı/anahtar kodları Concierge context'ine HİÇBİR ZAMAN girmez
    |   - Tüm tenant-aware işlemler tenant context ile korunur
    |   - Düşük güven = fail-closed = escalation
    */
    'guest_concierge_enabled' => (bool) env('GUEST_CONCIERGE_ENABLED', false),

    'guest_concierge_kill_switch' => (bool) env('GUEST_CONCIERGE_KILL_SWITCH', false),

    // PILOT-GATE-01: Allowlist loaded from config/concierge.php
    // This key is kept for backward compat; actual values come from concierge.php
    'guest_concierge_pilot_allowlist' => [
        'tenant_ids' => [],
        'reservation_ids' => [],
    ],

];

<?php

namespace App\Policies;

use App\Services\AI\EmailExtractionResult;
use Illuminate\Support\Facades\Log;

/**
 * CommunicationSeverityPolicy
 *
 * Deterministik PHP policy — LLM'e severity kararı BIRAKILMAZ.
 *
 * SAAB WAVE1 kararı:
 *   LLM sadece signal cikarir (intent, sentiment, is_urgent).
 *   P0/P1/P2 karari tamamen bu policy'nin sorumluluğundadir.
 *
 * Bu politika fail-open davranir:
 *   Bilinmeyen intent → P2 (en dusuk öncelik)
 *   LLM basarisiz → P2 (alarm yok, sessiz log)
 */
class CommunicationSeverityPolicy
{
    // ── Intent → Severity mappings ──────────────────────────────────────────

    /** P0: Ayni gun müdahale zorunlu */
    private const INTENT_P0 = [
        'checkin_lockout',
        'safety_incident',
        'health_emergency',
        'critical_complaint',
    ];

    /** P1: 24 saat icinde müdahale */
    private const INTENT_P1 = [
        'checkin_question',
        'checkout_confusion',
        'early_checkin_req',
        'late_checkout_req',
        'maintenance_issue',
        'pool_issue',
        'complaint',
    ];

    /** P2: Is gunu icinde halledilebilir */
    private const INTENT_P2 = [
        'general_question',
        'house_rules',
        'wifi_info',
        'parking_info',
        'area_question',
        'extend_stay',
        'damage_report',
    ];

    /**
     * Deterministic severity kararı.
     *
     * Kurallar (priority order):
     *   1. is_urgent = true → P0 zorla
     *   2. Intent P0 listesinde → P0
     *   3. Intent P1 listesinde → P1
     *   4. Intent P2 listesinde → P2
     *   5. Bilinmeyen intent → P2
     *
     * @return string P0 | P1 | P2
     */
    public static function determineSeverity(EmailExtractionResult $extraction): string
    {
        // Kural 1: Urgent signal zorla P0
        if ($extraction->isUrgent) {
            return 'P0';
        }

        // Kural 2–4: Intent mapping
        if (in_array($extraction->intent, self::INTENT_P0, true)) {
            return 'P0';
        }

        if (in_array($extraction->intent, self::INTENT_P1, true)) {
            return 'P1';
        }

        if (in_array($extraction->intent, self::INTENT_P2, true)) {
            return 'P2';
        }

        // Kural 5: Bilinmeyen intent → P2 (fail-open)
        return 'P2';
    }

    /**
     * P0 veya P1 — bildirim gerekli mi?
     * review_required → Ayhan'a bildirim gönderilir (manuel kontrol gerekli).
     * P2 alarm oluşturmaz.
     */
    public static function requiresNotification(string $severity): bool
    {
        return in_array($severity, ['P0', 'P1', 'review_required'], true);
    }

    /**
     * Cockpit'te gösterilmeli mi?
     * review_required → Cockpit'te kırmızı badge ile görünür.
     */
    public static function showInCockpit(string $severity): bool
    {
        return in_array($severity, ['P0', 'P1', 'P2', 'review_required'], true);
    }

    /**
     * Severity badge rengi Cockpit için.
     */
    public static function badgeColor(string $severity): string
    {
        return match ($severity) {
            'P0'             => 'red',
            'P1'             => 'orange',
            'P2'             => 'blue',
            'review_required' => 'yellow',
            default          => 'gray',
        };
    }

    // ── Fail-safe severity (Wave 2) ─────────────────────────────────────────

    /**
     * Fail-safe severity kararı — AI extraction sonucuna göre.
     *
     * Kural:
     *   1. classification_status = 'failed'  → review_required (LLM çöktü, manuel bakılmalı)
     *   2. classification_status = 'unclassified' → review_required (intent=unknown, risk var)
     *   3. classification_status = 'classified'   → standart policy (P0/P1/P2)
     *
     * SAAB Wave 2 kararı:
     *   AI bilinmiyorsa → sessizce P2'ye düşme YOK.
     *   Bilinmeyen → review_required → Ayhan bildirimi + Cockpit'te görünür.
     *
     * @param EmailExtractionResult|null $extraction  LLM sonucu (null = LLM crash)
     * @param string                   $status        'classified'|'unclassified'|'failed'
     * @return string P0|P1|P2|review_required
     */
    public static function determineSeverityWithFallback(
        ?EmailExtractionResult $extraction,
        string $classificationStatus,
    ): string {
        // Fail-safe: LLM başarısız veya intent bilinmiyor
        if ($classificationStatus !== 'classified') {
            Log::warning('[CommunicationSeverityPolicy] Fail-safe triggered', [
                'status' => $classificationStatus,
                'intent'  => $extraction?->intent ?? 'null',
            ]);
            return 'review_required';
        }

        // Normal classification — deterministic policy
        return self::determineSeverity($extraction);
    }
}

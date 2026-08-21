<?php

namespace App\Policies;

use App\Services\AI\EmailExtractionResult;

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
     * P2 alarm oluşturmaz.
     */
    public static function requiresNotification(string $severity): bool
    {
        return in_array($severity, ['P0', 'P1'], true);
    }

    /**
     * Cockpit'te gösterilmeli mi?
     */
    public static function showInCockpit(string $severity): bool
    {
        return in_array($severity, ['P0', 'P1', 'P2'], true);
    }
}

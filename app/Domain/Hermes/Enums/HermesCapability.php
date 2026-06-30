<?php

namespace App\Domain\Hermes\Enums;

/**
 * HermesCapability — Canonical Capability Names
 *
 * Team Hermes — Sprint 3.6 Epic 2: Corporate Ontology + Registry
 *
 * Bir ajanın sahip olduğu yetenekler bu enum üzerinden tanımlanır.
 * CapabilityRegistry bu enum üzerinden ajan → capability eşleştirmesi yapar.
 *
 * Naming Convention: {domain}.{action}
 * - domain: ajan veya yetenek grubu (küçük harf)
 * - action: ne yapar (notify, analyze, govern, execute...)
 */
enum HermesCapability: string
{
    // ─── Notification Capabilities ───────────────────────────────────
    case NOTIFY_PORTFOLIO_CREATED = 'notification.notify_portfolio_created';
    case NOTIFY_LEAD_CREATED = 'notification.notify_lead_created';
    case NOTIFY_GOVERNANCE_DECISION = 'notification.notify_governance_decision';
    case NOTIFY_EXECUTION_RESULT = 'notification.notify_execution_result';

    // ─── Analytics Capabilities ─────────────────────────────────────
    case ANALYZE_PORTFOLIO_TREND = 'analytics.analyze_portfolio_trend';
    case ANALYZE_LISTING_PERFORMANCE = 'analytics.analyze_listing_performance';
    case ANALYZE_LEAD_FLOW = 'analytics.analyze_lead_flow';

    // ─── Governance Capabilities ────────────────────────────────────
    case GOVERN_DECISION = 'governance.decide';
    case GOVERN_SUPPRESS = 'governance.suppress';
    case GOVERN_ROLLBACK = 'governance.rollback';

    // ─── Execution Capabilities ──────────────────────────────────────
    case EXECUTE_AUTO_FIX = 'execution.auto_fix';
    case EXECUTE_NOTIFICATION = 'execution.send_notification';
    case EXECUTE_CACHE_INVALIDATE = 'execution.invalidate_cache';

    // ─── Detection Capabilities ─────────────────────────────────────
    case DETECT_ANOMALY = 'detection.anomaly';
    case DETECT_FINDING = 'detection.finding';
    case DETECT_QUALITY_ISSUE = 'detection.quality_issue';

    // ─── Learning Capabilities ───────────────────────────────────────
    case LEARN_OPTIMIZE = 'learning.optimize';
    case LEARN_PATTERN_RECOGNITION = 'learning.pattern_recognition';

    /**
     * Get all capability names as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if a string is a valid capability
     */
    public static function isValid(string $capability): bool
    {
        return in_array($capability, self::values(), true);
    }

    /**
     * Get domain prefix from capability
     */
    public static function domain(string $capability): ?string
    {
        $parts = explode('.', $capability, 2);
        return $parts[0] ?? null;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::NOTIFY_PORTFOLIO_CREATED => 'Portföy Oluşturma Bildirimi',
            self::NOTIFY_LEAD_CREATED => 'Lead Oluşturma Bildirimi',
            self::NOTIFY_GOVERNANCE_DECISION => 'Governance Karar Bildirimi',
            self::NOTIFY_EXECUTION_RESULT => 'Execution Sonuç Bildirimi',
            self::ANALYZE_PORTFOLIO_TREND => 'Portföy Trendi Analizi',
            self::ANALYZE_LISTING_PERFORMANCE => 'İlan Performans Analizi',
            self::ANALYZE_LEAD_FLOW => 'Lead Akış Analizi',
            self::GOVERN_DECISION => 'Governance Karar Verme',
            self::GOVERN_SUPPRESS => 'Governance Bastırma',
            self::GOVERN_ROLLBACK => 'Governance Geri Alma',
            self::EXECUTE_AUTO_FIX => 'Otomatik Düzeltme',
            self::EXECUTE_NOTIFICATION => 'Bildirim Gönderme',
            self::EXECUTE_CACHE_INVALIDATE => 'Cache Geçersiz Kılma',
            self::DETECT_ANOMALY => 'Anomali Tespiti',
            self::DETECT_FINDING => 'Bulgı Tespiti',
            self::DETECT_QUALITY_ISSUE => 'Kalite Sorunu Tespiti',
            self::LEARN_OPTIMIZE => 'Optimizasyon Öğrenme',
            self::LEARN_PATTERN_RECOGNITION => 'Örüntü Tanıma',
        };
    }
}

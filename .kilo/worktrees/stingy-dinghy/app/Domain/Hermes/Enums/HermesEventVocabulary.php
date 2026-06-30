<?php

namespace App\Domain\Hermes\Enums;

/**
 * HermesEventVocabulary — Canonical Event Names
 *
 * Team Hermes — Sprint 3.6 Epic 2: Corporate Ontology + Registry
 *
 * Tüm domain event'leri bu enum üzerinden tanımlanır.
 * Hermes dispatcher event routing'i bu enum üzerinden yapar.
 *
 * Naming Convention: {domain}.{action}
 * - domain: ajan veya bounded context adı (küçük harf)
 * - action: geçmiş zaman (created, updated, deleted, detected, applied...)
 */
enum HermesEventVocabulary: string
{
    // ─── Portfolio / İlan Events ────────────────────────────────────
    case PORTFOLIO_CREATED = 'portfolio.created';
    case PORTFOLIO_UPDATED = 'portfolio.updated';
    case PORTFOLIO_DELETED = 'portfolio.deleted';
    case PORTFOLIO_PUBLISHED = 'portfolio.published';

    // ─── AI / Cortex Events ──────────────────────────────────────────
    case CORTEX_FINDING_DETECTED = 'cortex.finding_detected';
    case CORTEX_SCAN_COMPLETED = 'cortex.scan_completed';

    // ─── Governance / SAB Events ─────────────────────────────────────
    case GOVERNANCE_DECISION_MADE = 'governance.decision_made';
    case GOVERNANCE_FINDING_SUPPRESSED = 'governance.finding_suppressed';
    case GOVERNANCE_ROLLBACK_EXECUTED = 'governance.rollback_executed';
    case GOVERNANCE_OVERRIDE_APPLIED = 'governance.override_applied';
    case GOVERNANCE_ACTION_FAILED = 'governance.action_failed';

    // ─── Execution Events ────────────────────────────────────────────
    case EXECUTION_ACTION_APPLIED = 'execution.action_applied';
    case EXECUTION_ACTION_FAILED = 'execution.action_failed';

    // ─── Learning / Optimizer Events ─────────────────────────────────
    case OPTIMIZER_SUGGESTION_READY = 'optimizer.suggestion_ready';

    // ─── Watcher / Monitoring Events ─────────────────────────────────
    case WATCHER_ANOMALY_DETECTED = 'watcher.anomaly_detected';

    // ─── Lead / CRM Events ───────────────────────────────────────────
    case LEAD_CREATED = 'lead.created';
    case LEAD_ASSIGNED = 'lead.assigned';
    case LEAD_STATUS_CHANGED = 'lead.status_changed';

    // ─── Notification Events ─────────────────────────────────────────
    case NOTIFICATION_SENT = 'notification.sent';
    case NOTIFICATION_FAILED = 'notification.failed';

    /**
     * Get all event names as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if a string is a valid vocabulary event
     */
    public static function isValid(string $eventName): bool
    {
        return in_array($eventName, self::values(), true);
    }

    /**
     * Get domain prefix from event name
     */
    public static function domain(string $eventName): ?string
    {
        $parts = explode('.', $eventName, 2);
        return $parts[1] ?? null;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PORTFOLIO_CREATED => 'Yeni Portföy Oluşturuldu',
            self::PORTFOLIO_UPDATED => 'Portföy Güncellendi',
            self::PORTFOLIO_DELETED => 'Portföy Silindi',
            self::PORTFOLIO_PUBLISHED => 'Portföy Yayınlandı',
            self::CORTEX_FINDING_DETECTED => 'Cortex: Bulgı Tespit Edildi',
            self::CORTEX_SCAN_COMPLETED => 'Cortex: Tarama Tamamlandı',
            self::GOVERNANCE_DECISION_MADE => 'Governance: Karar Verildi',
            self::GOVERNANCE_FINDING_SUPPRESSED => 'Governance: Bulgı Bastırıldı',
            self::GOVERNANCE_ROLLBACK_EXECUTED => 'Governance: Geri Alma Çalıştı',
            self::GOVERNANCE_OVERRIDE_APPLIED => 'Governance: Override Uygulandı',
            self::GOVERNANCE_ACTION_FAILED => 'Governance: Eylem Başarısız',
            self::EXECUTION_ACTION_APPLIED => 'Execution: Eylem Uygulandı',
            self::EXECUTION_ACTION_FAILED => 'Execution: Eylem Başarısız',
            self::OPTIMIZER_SUGGESTION_READY => 'Optimizer: Öneri Hazır',
            self::WATCHER_ANOMALY_DETECTED => 'Watcher: Anomali Tespit Edildi',
            self::LEAD_CREATED => 'Lead: Oluşturuldu',
            self::LEAD_ASSIGNED => 'Lead: Atandı',
            self::LEAD_STATUS_CHANGED => 'Lead: Durum Değişti',
            self::NOTIFICATION_SENT => 'Bildirim: Gönderildi',
            self::NOTIFICATION_FAILED => 'Bildirim: Başarısız',
        };
    }
}

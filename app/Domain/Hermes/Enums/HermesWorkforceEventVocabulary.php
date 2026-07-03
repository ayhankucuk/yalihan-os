<?php

namespace App\Domain\Hermes\Enums;

/**
 * HermesEventVocabulary — AI Workforce Events
 *
 * Sprint 4.3: AI Workforce Vertical Slice
 * Sprint 4.4: DriveWorkspace Agent
 *
 * PortfolioCreated chain events:
 * portfolio.created → workforce.portfolio.analysis_requested
 *   → workforce.photo_analysis_requested
 *   → workforce.description_analysis_requested
 *   → workforce.notification_requested
 *   → workforce.workspace.created (DriveAgent output)
 */
enum HermesWorkforceEventVocabulary: string
{
    // ─── AI Workforce Chain Events ────────────────────────────────────
    case WORKFORCE_PORTFOLIO_ANALYSIS_REQUESTED = 'workforce.portfolio.analysis_requested';
    case WORKFORCE_PHOTO_ANALYSIS_REQUESTED = 'workforce.photo_analysis_requested';
    case WORKFORCE_DESCRIPTION_ANALYSIS_REQUESTED = 'workforce.description_analysis_requested';
    case WORKFORCE_NOTIFICATION_REQUESTED = 'workforce.notification_requested';

    // ─── DriveWorkspace Events — Sprint 4.4 ─────────────────────────
    case WORKFORCE_WORKSPACE_CREATED = 'workforce.workspace.created';

    // ─── Sprint 4.5 Digital Property Intelligence Events ────────────
    case WORKFORCE_PHOTO_ANALYSIS_COMPLETED = 'workforce.photo_analysis.completed';
    case WORKFORCE_DESCRIPTION_COMPLETED = 'workforce.description.completed';
    case WORKFORCE_PROPERTY_SCORE_CALCULATED = 'workforce.property_score.calculated';
    case WORKFORCE_PUBLISHING_DECISION_READY = 'workforce.publishing.decision_ready';

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
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::WORKFORCE_WORKSPACE_CREATED => 'AI Workforce: Çalışma Alanı Oluşturuldu',
            self::WORKFORCE_PHOTO_ANALYSIS_COMPLETED => 'AI Workforce: Fotoğraf Analizi Tamamlandı',
            self::WORKFORCE_DESCRIPTION_COMPLETED => 'AI Workforce: Açıklama Analizi Tamamlandı',
            self::WORKFORCE_PROPERTY_SCORE_CALCULATED => 'AI Workforce: Mülk Skoru Hesaplandı',
            self::WORKFORCE_PUBLISHING_DECISION_READY => 'AI Workforce: Yayınlama Kararı Hazır',
            default => $this->name, // Deprecated Sprint 4.3 events
        };
    }

    /**
     * Get chain order index (0 = first)
     */
    public function chainOrder(): int
    {
        return match ($this) {
            self::WORKFORCE_WORKSPACE_CREATED => 0,
            self::WORKFORCE_PHOTO_ANALYSIS_COMPLETED => 1,
            self::WORKFORCE_DESCRIPTION_COMPLETED => 2,
            self::WORKFORCE_PROPERTY_SCORE_CALCULATED => 3,
            self::WORKFORCE_PUBLISHING_DECISION_READY => 4,
            default => -1, // Deprecated
        };
    }
}

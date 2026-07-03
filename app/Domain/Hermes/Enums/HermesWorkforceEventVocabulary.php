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
            self::WORKFORCE_PORTFOLIO_ANALYSIS_REQUESTED => 'AI Workforce: Portföy Analizi İstendi',
            self::WORKFORCE_PHOTO_ANALYSIS_REQUESTED => 'AI Workforce: Fotoğraf Analizi İstendi',
            self::WORKFORCE_DESCRIPTION_ANALYSIS_REQUESTED => 'AI Workforce: Açıklama Analizi İstendi',
            self::WORKFORCE_NOTIFICATION_REQUESTED => 'AI Workforce: Bildirim İstendi',
            self::WORKFORCE_WORKSPACE_CREATED => 'AI Workforce: Drive Çalışma Alanı Oluşturuldu',
        };
    }

    /**
     * Get chain order index (0 = first)
     */
    public function chainOrder(): int
    {
        return match ($this) {
            self::WORKFORCE_PORTFOLIO_ANALYSIS_REQUESTED => 0,
            self::WORKFORCE_PHOTO_ANALYSIS_REQUESTED => 1,
            self::WORKFORCE_DESCRIPTION_ANALYSIS_REQUESTED => 2,
            self::WORKFORCE_NOTIFICATION_REQUESTED => 3,
            self::WORKFORCE_WORKSPACE_CREATED => 4,
        };
    }
}

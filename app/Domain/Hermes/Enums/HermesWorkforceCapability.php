<?php

namespace App\Domain\Hermes\Enums;

/**
 * HermesCapability — AI Workforce Capabilities
 *
 * Sprint 4.3: AI Workforce Vertical Slice
 * Sprint 4.4: DriveWorkspace Agent
 * Sprint 4.5: Digital Property Intelligence Platform
 */
enum HermesWorkforceCapability: string
{
    // ─── Drive Workspace Capabilities — Sprint 4.4 ─────────────────────
    case CREATE_DRIVE_WORKSPACE = 'workforce.create_drive_workspace';
    case MANAGE_DRIVE_WORKSPACE = 'workforce.manage_drive_workspace';

    // ─── Portfolio Agent Capabilities — Sprint 4.3 ─────────────────────
    case ANALYZE_PORTFOLIO = 'workforce.analyze_portfolio';
    case ENRICH_PORTFOLIO = 'workforce.enrich_portfolio';

    // ─── Photo Agent Capabilities ─────────────────────────────────────
    case ANALYZE_PHOTOS = 'workforce.analyze_photos';
    case SUGGEST_PHOTO_IMPROVEMENTS = 'workforce.suggest_photo_improvements';

    // ─── Description Agent Capabilities ───────────────────────────────
    case GENERATE_DESCRIPTION = 'workforce.generate_description';
    case IMPROVE_DESCRIPTION = 'workforce.improve_description';

    // ─── Property Score Agent Capabilities — Sprint 4.5 ──────────────
    case CALCULATE_PROPERTY_SCORE = 'workforce.calculate_property_score';
    case ANALYZE_QUALITY = 'workforce.analyze_quality';

    // ─── Publish Decision Agent Capabilities — Sprint 4.5 ───────────
    case DECIDE_PUBLISHING = 'workforce.decide_publishing';
    case EVALUATE_LISTING_QUALITY = 'workforce.evaluate_listing_quality';

    // ─── Notification Agent Capabilities ──────────────────────────────
    case SEND_PORTFOLIO_NOTIFICATION = 'workforce.send_portfolio_notification';
    case SEND_CHAIN_COMPLETE_NOTIFICATION = 'workforce.send_chain_complete_notification';

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
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATE_DRIVE_WORKSPACE => 'Drive Çalışma Alanı Oluştur',
            self::MANAGE_DRIVE_WORKSPACE => 'Drive Çalışma Alanı Yönet',
            self::ANALYZE_PORTFOLIO => 'Portföy Analizi',
            self::ENRICH_PORTFOLIO => 'Portföy Zenginleştirme',
            self::ANALYZE_PHOTOS => 'Fotoğraf Analizi',
            self::SUGGEST_PHOTO_IMPROVEMENTS => 'Fotoğraf İyileştirme Önerileri',
            self::GENERATE_DESCRIPTION => 'Açıklama Üretimi',
            self::IMPROVE_DESCRIPTION => 'Açıklama İyileştirme',
            self::CALCULATE_PROPERTY_SCORE => 'Mülk Skoru Hesaplama',
            self::ANALYZE_QUALITY => 'Kalite Analizi',
            self::DECIDE_PUBLISHING => 'Yayınlama Kararı',
            self::EVALUATE_LISTING_QUALITY => 'İlan Kalitesi Değerlendirme',
            self::SEND_PORTFOLIO_NOTIFICATION => 'Portföy Bildirimi Gönder',
            self::SEND_CHAIN_COMPLETE_NOTIFICATION => 'Zincir Tamamlama Bildirimi',
        };
    }
}

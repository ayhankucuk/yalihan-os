<?php

namespace App\Domain\Hermes\Enums;

/**
 * HermesCapability — AI Workforce Capabilities
 *
 * Sprint 4.3: AI Workforce Vertical Slice
 */
enum HermesWorkforceCapability: string
{
    // ─── Portfolio Agent Capabilities ──────────────────────────────────
    case ANALYZE_PORTFOLIO = 'workforce.analyze_portfolio';
    case ENRICH_PORTFOLIO = 'workforce.enrich_portfolio';

    // ─── Photo Agent Capabilities ─────────────────────────────────────
    case ANALYZE_PHOTOS = 'workforce.analyze_photos';
    case SUGGEST_PHOTO_IMPROVEMENTS = 'workforce.suggest_photo_improvements';

    // ─── Description Agent Capabilities ───────────────────────────────
    case GENERATE_DESCRIPTION = 'workforce.generate_description';
    case IMPROVE_DESCRIPTION = 'workforce.improve_description';

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
            self::ANALYZE_PORTFOLIO => 'Portföy Analizi',
            self::ENRICH_PORTFOLIO => 'Portföy Zenginleştirme',
            self::ANALYZE_PHOTOS => 'Fotoğraf Analizi',
            self::SUGGEST_PHOTO_IMPROVEMENTS => 'Fotoğraf İyileştirme Önerileri',
            self::GENERATE_DESCRIPTION => 'Açıklama Üretimi',
            self::IMPROVE_DESCRIPTION => 'Açıklama İyileştirme',
            self::SEND_PORTFOLIO_NOTIFICATION => 'Portföy Bildirimi Gönder',
            self::SEND_CHAIN_COMPLETE_NOTIFICATION => 'Zincir Tamamlama Bildirimi',
        };
    }
}

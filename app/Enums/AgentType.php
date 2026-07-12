<?php

namespace App\Enums;

/**
 * AgentType — AI Workforce ajan türleri.
 *
 * Sprint 7.2 — AI Workforce Foundation
 *
 * @enum-extractable
 */
enum AgentType: string
{
    // ── Workforce Foundation ──
    case WORKFORCE_ORCHESTRATOR = 'workforce_orchestrator';
    case LISTING_AGENT           = 'listing_agent';
    case PUBLISHING_AGENT        = 'publishing_agent';

    // ── Feature Workforce ──
    case PHOTO_AGENT             = 'photo_agent';
    case DESCRIPTION_AGENT        = 'description_agent';
    case PROPERTY_SCORE_AGENT     = 'property_score_agent';
    case PUBLISH_DECISION_AGENT  = 'publish_decision_agent';

    // ── CRM Workforce ──
    case CRM_AGENT               = 'crm_agent';
    case MATCHING_AGENT          = 'matching_agent';
    case NOTIFICATION_AGENT      = 'notification_agent';

    // ── Market Intelligence ──
    case MARKET_AGENT            = 'market_agent';
    case PRICING_AGENT          = 'pricing_agent';

    // ── Drive / Workspace ──
    case DRIVE_AGENT             = 'drive_agent';

    // ── Fallback ──
    case OTHER                  = 'other';

    public function label(): string
    {
        return match($this) {
            self::WORKFORCE_ORCHESTRATOR => 'Workforce Orkestratör',
            self::LISTING_AGENT          => 'İlan Ajanı',
            self::PUBLISHING_AGENT       => 'Yayınlama Ajanı',
            self::PHOTO_AGENT            => 'Fotoğraf Ajanı',
            self::DESCRIPTION_AGENT     => 'Açıklama Ajanı',
            self::PROPERTY_SCORE_AGENT  => 'Mülk Skor Ajanı',
            self::PUBLISH_DECISION_AGENT => 'Yayın Karar Ajanı',
            self::CRM_AGENT              => 'CRM Ajanı',
            self::MATCHING_AGENT        => 'Eşleştirme Ajanı',
            self::NOTIFICATION_AGENT     => 'Bildirim Ajanı',
            self::MARKET_AGENT          => 'Piyasa Ajanı',
            self::PRICING_AGENT         => 'Fiyatlandırma Ajanı',
            self::DRIVE_AGENT           => 'Drive Ajanı',
            self::OTHER                  => 'Diğer',
        };
    }

    /** Bu ajan türü bir Workforce ajanı mı? */
    public function isWorkforce(): bool
    {
        return in_array($this, [
            self::LISTING_AGENT,
            self::PUBLISHING_AGENT,
            self::CRM_AGENT,
            self::MARKET_AGENT,
        ]);
    }
}

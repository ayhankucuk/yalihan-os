<?php

namespace App\Domain\Workspace\Enums;

/**
 * WorkspaceState — Property Digital Twin Lifecycle State Machine
 *
 * Sprint 4.5 — Digital Property Intelligence Platform
 *
 * State transitions (event-driven):
 *
 * DRAFT
 *   ↓ (PortfolioCreated event)
 * WORKSPACE_CREATED
 *   ↓ (PhotoAgent completes)
 * MEDIA_READY
 *   ↓ (DescriptionAgent completes)
 * DESCRIPTION_READY
 *   ↓ (PropertyScoreAgent completes)
 * QUALITY_CHECKED
 *   ↓ (PublishDecisionAgent decides)
 * READY_FOR_PUBLISH
 *   ↓ (manual or PublishAgent)
 * PUBLISHED
 *   ↓ (listing goes live)
 * ACTIVE
 *   ↓ (archived by advisor)
 * ARCHIVED
 */
enum WorkspaceState: string
{
    // ─── Initial State ────────────────────────────────────────────────
    case DRAFT = 'draft';

    // ─── Workspace Created ───────────────────────────────────────────
    case WORKSPACE_CREATED = 'workspace_created';

    // ─── Media Phase ────────────────────────────────────────────────
    case MEDIA_READY = 'media_ready';

    // ─── Content Phase ───────────────────────────────────────────────
    case DESCRIPTION_READY = 'description_ready';

    // ─── Quality Phase ──────────────────────────────────────────────
    case QUALITY_CHECKED = 'quality_checked';

    // ─── Publishing Phase ───────────────────────────────────────────
    case READY_FOR_PUBLISH = 'ready_for_publish';

    // ─── Live States ────────────────────────────────────────────────
    case PUBLISHED = 'published';
    case ACTIVE = 'active';

    // ─── Terminal State ────────────────────────────────────────────
    case ARCHIVED = 'archived';

    /**
     * Get human-readable Turkish label
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Taslak',
            self::WORKSPACE_CREATED => 'Çalışma Alanı Oluşturuldu',
            self::MEDIA_READY => 'Medya Hazır',
            self::DESCRIPTION_READY => 'Açıklama Hazır',
            self::QUALITY_CHECKED => 'Kalite Kontrol Yapıldı',
            self::READY_FOR_PUBLISH => 'Yayına Hazır',
            self::PUBLISHED => 'Yayınlandı',
            self::ACTIVE => 'Aktif',
            self::ARCHIVED => 'Arşivlendi',
        };
    }

    /**
     * Get color for UI (Tailwind CSS class name)
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::WORKSPACE_CREATED => 'blue',
            self::MEDIA_READY => 'cyan',
            self::DESCRIPTION_READY => 'indigo',
            self::QUALITY_CHECKED => 'violet',
            self::READY_FOR_PUBLISH => 'amber',
            self::PUBLISHED => 'green',
            self::ACTIVE => 'emerald',
            self::ARCHIVED => 'slate',
        };
    }

    /**
     * Check if transition to target state is valid
     */
    public function canTransitionTo(WorkspaceState $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Get allowed next states
     *
     * @return array<WorkspaceState>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [
                self::WORKSPACE_CREATED,
                self::ARCHIVED,
            ],
            self::WORKSPACE_CREATED => [
                self::MEDIA_READY,
                self::DESCRIPTION_READY,
                self::ARCHIVED,
            ],
            self::MEDIA_READY => [
                self::DESCRIPTION_READY,
                self::ARCHIVED,
            ],
            self::DESCRIPTION_READY => [
                self::QUALITY_CHECKED,
                self::ARCHIVED,
            ],
            self::QUALITY_CHECKED => [
                self::READY_FOR_PUBLISH,
                self::MEDIA_READY, // Can go back if quality fails
                self::ARCHIVED,
            ],
            self::READY_FOR_PUBLISH => [
                self::PUBLISHED,
                self::QUALITY_CHECKED, // Can go back
                self::ARCHIVED,
            ],
            self::PUBLISHED => [
                self::ACTIVE,
                self::READY_FOR_PUBLISH, // Unpublish
                self::ARCHIVED,
            ],
            self::ACTIVE => [
                self::ARCHIVED,
            ],
            self::ARCHIVED => [], // Terminal state
        };
    }

    /**
     * Get the step number in the lifecycle (0-indexed)
     */
    public function step(): int
    {
        return match ($this) {
            self::DRAFT => 0,
            self::WORKSPACE_CREATED => 1,
            self::MEDIA_READY => 2,
            self::DESCRIPTION_READY => 3,
            self::QUALITY_CHECKED => 4,
            self::READY_FOR_PUBLISH => 5,
            self::PUBLISHED => 6,
            self::ACTIVE => 7,
            self::ARCHIVED => 8,
        };
    }

    /**
     * Get total steps in active lifecycle (excluding terminal states)
     */
    public static function activeLifecycleSteps(): int
    {
        return 7; // DRAFT → WORKSPACE_CREATED → ... → ACTIVE
    }

    /**
     * Calculate completion percentage for active lifecycle
     */
    public function completionPercent(): float
    {
        return round(($this->step() / self::activeLifecycleSteps()) * 100, 1);
    }

    /**
     * Check if this is a terminal state
     */
    public function isTerminal(): bool
    {
        return $this === self::ARCHIVED;
    }

    /**
     * Check if this is an active (live) state
     */
    public function isLive(): bool
    {
        return in_array($this, [self::PUBLISHED, self::ACTIVE], true);
    }

    /**
     * Check if this state is before publishing
     */
    public function isPrePublishing(): bool
    {
        return $this->step() < self::PUBLISHED->step();
    }
}

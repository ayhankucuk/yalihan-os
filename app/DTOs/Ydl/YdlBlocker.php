<?php

namespace App\DTOs\Ydl;

/**
 * YdlBlocker — Immutable DTO for a single blocker entry.
 *
 * YDL v1 Phase 1
 *
 * @readonly
 */
final class YdlBlocker
{
    public const TYPE_EXTERNAL_DEPENDENCY    = 'EXTERNAL_DEPENDENCY';
    public const TYPE_INTERNAL_BLOCKER      = 'INTERNAL_BLOCKER';
    public const TYPE_INFRASTRUCTURE_ISSUE  = 'INFRASTRUCTURE_ISSUE';
    public const TYPE_SECURITY_ISSUE        = 'SECURITY_ISSUE';
    public const TYPE_TEST_INSTABILITY       = 'TEST_INSTABILITY';

    public const ACTION_DO_NOT_CONTINUE     = 'DO_NOT_CONTINUE';
    public const ACTION_FIX_REQUIRED        = 'FIX_REQUIRED';
    public const ACTION_ESCALATE           = 'ESCALATE';
    public const ACTION_STOP_IMMEDIATELY    = 'STOP_IMMEDIATELY';
    public const ACTION_PARALLEL_OK          = 'PARALLEL_OK';

    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_RESOLVED = 'RESOLVED';

    public function __construct(
        public readonly string  $id,
        public readonly string  $gate,
        public readonly string  $sprint,
        public readonly string  $type,
        public readonly string  $owner,
        public readonly string  $reason,
        public readonly string  $developmentAction,
        public readonly string  $status,
        public readonly string  $createdAt,
        public readonly ?string $resolvedAt = null,
        public readonly ?string $resolutionNote = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:                  $data['id'] ?? '',
            gate:                $data['gate'] ?? '',
            sprint:              $data['sprint'] ?? '',
            type:                $data['type'] ?? self::TYPE_EXTERNAL_DEPENDENCY,
            owner:               $data['owner'] ?? '',
            reason:              $data['reason'] ?? '',
            developmentAction:   $data['development_action'] ?? self::ACTION_DO_NOT_CONTINUE,
            status:              $data['status'] ?? self::STATUS_ACTIVE,
            createdAt:           $data['created_at'] ?? now()->toIso8601String(),
            resolvedAt:          $data['resolved_at'] ?? null,
            resolutionNote:      $data['resolution_note'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'gate'                => $this->gate,
            'sprint'              => $this->sprint,
            'type'                => $this->type,
            'owner'               => $this->owner,
            'reason'              => $this->reason,
            'development_action'   => $this->developmentAction,
            'status'              => $this->status,
            'created_at'          => $this->createdAt,
            'resolved_at'         => $this->resolvedAt,
            'resolution_note'     => $this->resolutionNote,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function allowsParallelWork(): bool
    {
        return match ($this->type) {
            self::TYPE_EXTERNAL_DEPENDENCY => true,
            self::TYPE_INFRASTRUCTURE_ISSUE => true,
            self::TYPE_TEST_INSTABILITY => false,
            self::TYPE_SECURITY_ISSUE => false,
            self::TYPE_INTERNAL_BLOCKER => false,
            default => false,
        };
    }

    public function requiresStop(): bool
    {
        return $this->type === self::TYPE_SECURITY_ISSUE;
    }

    public function blocksAllDevelopment(): bool
    {
        return $this->requiresStop();
    }
}

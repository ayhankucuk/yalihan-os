<?php

namespace App\Domain\ChannelManager\Enums;

/**
 * SyncState — Operational status
 *
 * CHANNEL_MANAGER Wave 1: Foundation
 *
 * Represents the state of a sync operation throughout its lifecycle.
 */
enum SyncState: string
{
    case PENDING     = 'pending';
    case IN_PROGRESS = 'in_progress';
    case SUCCESS     = 'success';
    case FAILED      = 'failed';
    case DRIFTED     = 'drifted';
    case PARTIAL     = 'partial'; // Some items succeeded

    /**
     * Check if this is a terminal state
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::SUCCESS,
            self::FAILED,
            self::DRIFTED => true,
            self::PENDING,
            self::IN_PROGRESS,
            self::PARTIAL  => false,
        };
    }

    /**
     * Check if this state indicates a successful operation
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Check if this state indicates a failed operation
     */
    public function isFailed(): bool
    {
        return match ($this) {
            self::FAILED,
            self::DRIFTED => true,
            self::SUCCESS,
            self::PENDING,
            self::IN_PROGRESS,
            self::PARTIAL  => false,
        };
    }

    /**
     * Get display name
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING     => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::SUCCESS     => 'Success',
            self::FAILED      => 'Failed',
            self::DRIFTED     => 'Drifted',
            self::PARTIAL     => 'Partial Success',
        };
    }
}

<?php

namespace Tests\Unit\ChannelManager;

use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;
use App\Domain\ChannelManager\Enums\SyncState;
use PHPUnit\Framework\TestCase;

/**
 * ChannelSyncEnumsTest
 *
 * CHANNEL_MANAGER Wave 1: Enum Tests
 */
class ChannelSyncEnumsTest extends TestCase
{
    /** @test */
    public function channel_values_are_correct(): void
    {
        $this->assertEquals('airbnb', Channel::AIRBNB->value);
        $this->assertEquals('booking', Channel::BOOKING->value);
        $this->assertEquals('ical', Channel::ICAL->value);
        $this->assertEquals('ical', Channel::ICAL->value);
    }

    /** @test */
    public function channel_priority_tiers_are_correct(): void
    {
        // Manual = TIER_OWNER_BLOCK = 3
        $this->assertEquals(3, Channel::MANUAL->priorityTier());
        // External channels = TIER_EXTERNAL_SYNC = 4
        $this->assertEquals(4, Channel::AIRBNB->priorityTier());
        $this->assertEquals(4, Channel::BOOKING->priorityTier());
        $this->assertEquals(4, Channel::ICAL->priorityTier());
    }

    /** @test */
    public function channel_labels_are_readable(): void
    {
        $this->assertEquals('Airbnb', Channel::AIRBNB->label());
        $this->assertEquals('Booking.com', Channel::BOOKING->label());
        $this->assertEquals('iCal', Channel::ICAL->label());
    }

    /** @test */
    public function channel_supports_operations(): void
    {
        // iCal supports both
        $this->assertTrue(Channel::ICAL->supportsPush());
        $this->assertTrue(Channel::ICAL->supportsPull());
        // Manual doesn't support sync
        $this->assertFalse(Channel::MANUAL->supportsPush());
        $this->assertFalse(Channel::MANUAL->supportsPull());
    }

    /** @test */
    public function sync_direction_opposite(): void
    {
        $this->assertEquals(SyncDirection::IMPORT, SyncDirection::EXPORT->opposite());
        $this->assertEquals(SyncDirection::EXPORT, SyncDirection::IMPORT->opposite());
    }

    /** @test */
    public function sync_state_is_terminal(): void
    {
        $this->assertTrue(SyncState::SUCCESS->isTerminal());
        $this->assertTrue(SyncState::FAILED->isTerminal());
        $this->assertTrue(SyncState::DRIFTED->isTerminal());
        $this->assertFalse(SyncState::PENDING->isTerminal());
        $this->assertFalse(SyncState::IN_PROGRESS->isTerminal());
        $this->assertFalse(SyncState::PARTIAL->isTerminal());
    }

    /** @test */
    public function sync_state_is_success(): void
    {
        $this->assertTrue(SyncState::SUCCESS->isSuccess());
        $this->assertFalse(SyncState::FAILED->isSuccess());
        $this->assertFalse(SyncState::PARTIAL->isSuccess());
    }

    /** @test */
    public function sync_state_is_failed(): void
    {
        $this->assertTrue(SyncState::FAILED->isFailed());
        $this->assertTrue(SyncState::DRIFTED->isFailed());
        $this->assertFalse(SyncState::SUCCESS->isFailed());
        $this->assertFalse(SyncState::PENDING->isFailed());
    }
}

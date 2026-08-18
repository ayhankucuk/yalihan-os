<?php

namespace App\Domain\ChannelManager\Contracts;

use App\Domain\ChannelManager\Models\ChannelApiResponse;

/**
 * ChannelAdapter — Interface for external channel integrations
 *
 * Sprint 13 E01: Domain Foundation
 *
 * Each channel (Airbnb, Booking, Sahibinden) implements this contract.
 */
interface ChannelAdapter
{
    /**
     * Get the channel identifier
     */
    public function getChannelId(): string;

    /**
     * Get the channel display name
     */
    public function getChannelName(): string;

    /**
     * Push availability to the channel
     *
     * @param array $availabilityData ['date' => 'Y-m-d', 'available' => bool, 'property_id' => int]
     * @return ChannelApiResponse
     */
    public function pushAvailability(array $availabilityData): ChannelApiResponse;

    /**
     * Pull availability from the channel
     *
     * @param string $fromDate Y-m-d
     * @param string $toDate Y-m-d
     * @return ChannelApiResponse
     */
    public function pullAvailability(string $fromDate, string $toDate): ChannelApiResponse;

    /**
     * Push a reservation to the channel
     *
     * @param array $reservationData
     * @return ChannelApiResponse
     */
    public function pushReservation(array $reservationData): ChannelApiResponse;

    /**
     * Fetch channel connection/status
     *
     * @return ChannelApiResponse
     */
    public function fetchStatus(): ChannelApiResponse;
}

<?php

namespace App\Services\Property;

use App\Models\Property;

class PropertyStateMachine
{
    public const STATE_DRAFT = 'DRAFT';
    public const STATE_VERIFIED = 'VERIFIED';
    public const STATE_ACTIVE = 'ACTIVE'; // context7-ignore
    public const STATE_ARCHIVED = 'ARCHIVED';

    /**
     * Transition a property's state.
     */
    public function transition(Property $property, string $targetState): void
    {
        $currentState = $property->aktiflik_durumu;

        if ($currentState === $targetState) {
            return;
        }

        switch ($targetState) {
            case self::STATE_VERIFIED:
                $this->transitionToVerified($property);
                break;

            case self::STATE_ACTIVE:
                $this->transitionToActive($property);
                break;

            case self::STATE_ARCHIVED:
                $this->transitionToArchived($property);
                break;

            default:
                throw new \InvalidArgumentException("Invalid target state: {$targetState}");
        }

        $property->aktiflik_durumu = $targetState;
    }

    protected function transitionToVerified(Property $property): void
    {
        if ($property->aktiflik_durumu !== self::STATE_DRAFT) {
            throw new \DomainException("Can only transition to VERIFIED from DRAFT state. Current: {$property->aktiflik_durumu}");
        }

        // Enforce invariants: Location & TapuInfo must be fully populated
        $location = $property->getLocation();
        $tapuInfo = $property->getTapuInfo();

        if (
            empty($location->getIlId()) ||
            empty($location->getIlceId()) ||
            empty($location->getMahalleId()) ||
            empty($location->getLat()) ||
            empty($location->getLng())
        ) {
            throw new \DomainException("Cannot transition to VERIFIED: Location coordinates and region IDs must be set.");
        }

        if (
            empty($tapuInfo->getAda()) ||
            empty($tapuInfo->getParsel())
        ) {
            throw new \DomainException("Cannot transition to VERIFIED: Tapu ada/parsel references must be set.");
        }
    }

    protected function transitionToActive(Property $property): void
    {
        if ($property->aktiflik_durumu !== self::STATE_VERIFIED) {
            throw new \DomainException("Can only transition to ACTIVE from VERIFIED state. Current: {$property->aktiflik_durumu}"); // context7-ignore
        }
    }

    protected function transitionToArchived(Property $property): void
    {
        // Property can be archived from any state
    }
}

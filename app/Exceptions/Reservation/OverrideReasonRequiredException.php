<?php

namespace App\Exceptions\Reservation;

/**
 * OverrideReasonRequiredException
 *
 * CONFLICT_DETECTION Phase 3C — Override Authorization
 *
 * Thrown when an override is attempted without a mandatory justification reason.
 */
class OverrideReasonRequiredException extends \Exception
{
    public function __construct()
    {
        parent::__construct(
            "Override reason is required. Every conflict override must include an explicit justification."
        );
    }
}

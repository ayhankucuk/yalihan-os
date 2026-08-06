<?php

namespace App\Exceptions\Reservation;

/**
 * OverrideNotAuthorizedException
 *
 * CONFLICT_DETECTION Phase 3C — Override Authorization
 *
 * Thrown when an actor attempts to override a conflict without authorization.
 */
class OverrideNotAuthorizedException extends \Exception
{
    public function __construct(int $actorUserId)
    {
        parent::__construct(
            "Override not authorized: user {$actorUserId} does not have override privileges. " .
            "Only admin and super-admin roles may override conflict blocks."
        );
    }
}

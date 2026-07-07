<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace\Exceptions;

use Exception;

/**
 * Class InvalidStateTransitionException
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Thrown when an invalid state transition is attempted.
 *
 * @package App\Domain\PropertyWorkspace\Exceptions
 */
final class InvalidStateTransitionException extends Exception
{
    public function __construct(
        public readonly string $fromState,
        public readonly string $toState,
        string $message = '',
    ) {
        if ($message === '') {
            $message = "Invalid state transition from '{$fromState}' to '{$toState}'";
        }
        parent::__construct($message);
    }
}

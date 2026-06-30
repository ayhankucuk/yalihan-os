<?php

namespace App\Exceptions\Governance;

use Exception;

/**
 * Class ProjectionSequenceException
 * @package App\Exceptions\Governance
 * @description Exception thrown when CQRS event sequence or source sync drifts are detected.
 */
class ProjectionSequenceException extends Exception
{
    //
}

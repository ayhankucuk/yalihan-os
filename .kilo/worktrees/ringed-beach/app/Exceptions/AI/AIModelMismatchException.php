<?php

namespace App\Exceptions\AI;

use Exception;

class AIModelMismatchException extends Exception
{
    public function __construct(string $expected, string $actual)
    {
        parent::__construct(sprintf('AI Model mismatch. Expected: %s, Actual: %s', $expected, $actual));
    }
}

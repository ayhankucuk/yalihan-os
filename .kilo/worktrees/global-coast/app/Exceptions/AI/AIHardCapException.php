<?php

namespace App\Exceptions\AI;

use Exception;

class AIHardCapException extends Exception
{
    public function __construct(string $message = "AI Usage Hard Cap Reached")
    {
        parent::__construct($message, 429);
    }
}

<?php

namespace App\Exceptions;

use Exception;

/**
 * Context7 Protokol İhlali İstisnası
 *
 * @context7-ignore-file
 */
class Context7ViolationException extends Exception
{
    protected $violationData;

    public function __construct(string $message, array $violationData = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->violationData = $violationData;
    }

    public function getViolationData(): array
    {
        return $this->violationData;
    }
}

<?php

namespace App\Exceptions\Copilot;

use RuntimeException;

class OutputContractViolationException extends RuntimeException
{
    public static function invalidJson(string $rawOutput): self
    {
        return new self('Copilot output is not valid JSON. Raw output: ' . mb_substr($rawOutput, 0, 1000));
    }

    public static function missingField(string $field): self
    {
        return new self("Copilot output contract violation: missing required field [{$field}].");
    }

    public static function invalidField(string $field, string $reason): self
    {
        return new self("Copilot output contract violation: invalid field [{$field}] - {$reason}.");
    }

    public static function forbiddenField(string $field): self
    {
        return new self("Copilot output contract violation: forbidden field [{$field}].");
    }
}

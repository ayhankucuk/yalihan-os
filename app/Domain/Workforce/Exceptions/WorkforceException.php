<?php

namespace App\Domain\Workforce\Exceptions;

use App\Domain\Workforce\DTO\WorkforceContext;
use App\Domain\Workforce\DTO\WorkforceResult;
use App\Enums\AgentStatus;

/**
 * WorkforceException — AI Workforce katmanı hata sınıfı.
 *
 * Sprint 7.2 — AI Workforce Foundation
 */
class WorkforceException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $agentType,
        public readonly ?WorkforceContext $context = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Bu hatadan WorkforceResult oluştur.
     */
    public function toResult(): WorkforceResult
    {
        return WorkforceResult::failure(
            agent: $this->agentType instanceof \BackedEnum
                ? $this->agentType
                : \App\Enums\AgentType::OTHER,
            error: $this->getMessage(),
        );
    }
}

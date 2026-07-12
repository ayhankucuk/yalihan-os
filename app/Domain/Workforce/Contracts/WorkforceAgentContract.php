<?php

namespace App\Domain\Workforce\Contracts;

use App\Domain\Workforce\DTO\WorkforceContext;
use App\Domain\Workforce\DTO\WorkforceResult;
use App\Enums\AgentType;

/**
 * WorkforceAgentContract — Tüm ajanların uygulaması gereken kontrat.
 *
 * Sprint 7.2 — AI Workforce Foundation
 */
interface WorkforceAgentContract
{
    public function handle(WorkforceContext $context): WorkforceResult;
    public function getType(): AgentType;
    public function canHandle(WorkforceContext $context): bool;
    public function description(): string;
}

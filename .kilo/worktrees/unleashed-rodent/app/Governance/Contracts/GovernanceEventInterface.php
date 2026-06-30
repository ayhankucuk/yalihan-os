<?php

namespace App\Governance\Contracts;

/**
 * Phase 4C — Immutable Governance Event Contract
 *
 * Safety Guardrail #3: Tüm telemetri eventleri immutable olmalı.
 * Oluşturulduktan sonra hiçbir alan değiştirilemez.
 */
interface GovernanceEventInterface
{
    public function getEventId(): string;

    public function getMetric(): string;

    public function getTags(): array;

    public function getTenantId(): ?int;

    public function getTraceId(): ?string;

    public function getRequestId(): ?string;

    public function isViolation(): bool;

    public function getViolationType(): ?string;

    public function getSeverity(): string;

    public function getSourceClass(): ?string;

    public function getOccurredAt(): \DateTimeImmutable;
}

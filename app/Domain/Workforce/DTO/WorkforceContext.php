<?php

namespace App\Domain\Workforce\DTO;

use App\Models\PortfolioDriveWorkspace;
use App\Models\User;

/**
 * WorkforceContext — Tüm ajanların ortak kullandığı bağlam nesnesi.
 *
 * Sprint 7.2 — AI Workforce Foundation
 *
 * Bir ajan çağrılmadan önce oluşturulur ve tüm ajanlar arasında taşınır.
 * Tenant-safe, immutable-style (readonly after construct).
 */
final class WorkforceContext
{
    public function __construct(
        public readonly ?PortfolioDriveWorkspace $workspace = null,
        public readonly ?int $tenantId = null,
        public readonly ?int $userId = null,
        public readonly ?User $user = null,
        /** @var array<string, mixed> Agent-agnostic metadata bag */
        public readonly array $metadata = [],
        /** @var array<string, mixed> Geçici veri — ajanlar arası veri taşıma */
        public readonly array $sharedData = [],
    ) {}

    /**
     * Yeni context + metadata ile yeni instance döndür ( immutable append )
     */
    public function withMetadata(array $追加metadata): self
    {
        return new self(
            workspace: $this->workspace,
            tenantId: $this->tenantId,
            userId: $this->userId,
            user: $this->user,
            metadata: array_merge($this->metadata, $追加metadata),
            sharedData: $this->sharedData,
        );
    }

    /**
     * Yeni context + sharedData ile yeni instance döndür ( immutable append )
     */
    public function withSharedData(array $sharedData): self
    {
        return new self(
            workspace: $this->workspace,
            tenantId: $this->tenantId,
            userId: $this->userId,
            user: $this->user,
            metadata: $this->metadata,
            sharedData: array_merge($this->sharedData, $sharedData),
        );
    }

    /**
     * Workspace yoksa null döndürür.
     */
    public function getWorkspaceId(): ?int
    {
        return $this->workspace?->getKey();
    }

    /**
     * Tenant ID workspace'tan çıkarılamazsa fallback döndür.
     */
    public function resolveTenantId(): int
    {
        return $this->tenantId
            ?? $this->workspace?->tenant_id
            ?? $this->user?->tenant_id
            ?? throw new \RuntimeException('Tenant ID resolve edilemedi');
    }
}

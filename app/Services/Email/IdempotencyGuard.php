<?php

namespace App\Services\Email;

/**
 * IdempotencyGuard
 *
 * Gmail Message-ID bazlı duplicate koruması.
 * Aynı tenant + external_message_id kombinasyonu DB'de varsa email atlanır.
 * HermesEventLog' da ayrıca event replay koruması sağlar.
 */
class IdempotencyGuard
{
    public function __construct(
        private readonly \App\Models\Communication $communication,
    ) {}

    /**
     * Bu mesaj daha önce işlenmiş mi?
     */
    public function isDuplicate(int $tenantId, string $externalMessageId): bool
    {
        return $this->communication
            ->where('tenant_id', $tenantId)
            ->where('external_message_id', $externalMessageId)
            ->exists();
    }

    /**
     * Duplicate ise mevcut kaydı döndür.
     */
    public function findExisting(int $tenantId, string $externalMessageId): ?\App\Models\Communication
    {
        return $this->communication
            ->where('tenant_id', $tenantId)
            ->where('external_message_id', $externalMessageId)
            ->first();
    }
}

<?php

namespace App\Domain\Core;

/**
 * Interface BoundedContextContract
 * @package App\Domain\Core
 * @description Phase 16 kapsamında monolitten ayrıştırılacak tüm kurumsal dikey dilimler için anayasal sınır kapısı kontratı.
 */
interface BoundedContextContract
{
    /**
     * Dikey dilimin benzersiz etki alanı (Domain) adını deklare eder.
     * Context7 Kanonik Sözlük Yasası'na tabidir.
     *
     * @return string
     */
    public function getDomainIdentifier(): string;

    /**
     * İlgili etki alanına ait tüm iş mantığı (business mutations) operasyonlarının
     * tenant bağlamını katı bir şekilde doğrular (SAB Madde 1).
     *
     * @param int $tenantId
     * @return bool
     */
    public function validateTenantBoundary(int $tenantId): bool;
}

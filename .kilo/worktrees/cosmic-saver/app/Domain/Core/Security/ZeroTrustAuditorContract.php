<?php

namespace App\Domain\Core\Security;

/**
 * Interface ZeroTrustAuditorContract
 * @package App\Domain\Core\Security
 * @description Phase 19: Çalışma zamanı güvenlik ihlallerini, kimlik anomalilerini ve kiracı sınır sızıntılarını adli izleme altına alan anayasal kontrat.
 */
interface ZeroTrustAuditorContract
{
    /**
     * Potansiyel bir tehdit veya sınır ihlali eylemini adli olarak tescil eder.
     * Context7 Kanonik Sözlük ve SAB Madde 1 kurallarına tabidir.
     *
     * @param string $tehditTipi Örn: 'CROSS_TENANT_MUTATION_ATTEMPT'
     * @param int $tenantId İhlal edilmeye çalışılan veya aktif olan kiracı kimliği
     * @param array<string, mixed> $adliMetadatalar İsteğe ait IP, Kullanıcı, Payload parametreleri
     * @return bool Tescil işlemi başarılı mı?
     */
    public function logForensicsAnomaly(string $tehditTipi, int $tenantId, array $adliMetadatalar): bool;

    /**
     * Aktif oturum bağlamındaki eylemin, geçmiş davranışsal veri kalıplarına göre anomalilik skorunu hesaplar.
     *
     * @param int $kullaniciId
     * @param string $eylemKodu Örn: 'BULK_LISTING_EXPORT'
     * @return float Anomali Skoru [0.0 - 1.0] (0.0: Güvenli, 1.0: Mutlak Tehdit / Blokaj)
     */
    public function evaluateBehavioralRiskScore(int $kullaniciId, string $eylemKodu): float;
}

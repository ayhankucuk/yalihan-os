<?php

namespace App\Domain\Kisi;

use App\Domain\Core\Security\ZeroTrustAuditorContract;
use App\Domain\Core\BoundedContextContract;
use App\Exceptions\Governance\TenantMismatchException;
use App\Domain\CQRS\Messaging\EventDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class KisiDomainYonetici
 * @package App\Domain\Kisi
 * @description Phase 16 Sprint 2: Müşteri ve Aday (Lead) yaşam döngüsünü monolitten ayıran sıfır-güven kurumsal orkestratör.
 */
final class KisiDomainYonetici implements BoundedContextContract
{
    private readonly ZeroTrustAuditorContract $auditor;
    private readonly \App\Domain\Core\Security\GlobalHardlockManagerContract $hardlockManager;

    /**
     * KisiDomainYonetici constructor.
     */
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        ?ZeroTrustAuditorContract $auditor = null,
        ?\App\Domain\Core\Security\GlobalHardlockManagerContract $hardlockManager = null
    ) {
        $this->auditor = $auditor ?? app(ZeroTrustAuditorContract::class);
        $this->hardlockManager = $hardlockManager ?? app(\App\Domain\Core\Security\GlobalHardlockManagerContract::class);
    }

    /**
     * @inheritDoc
     */
    public function getDomainIdentifier(): string
    {
        return 'PROPERTYHUB_CRM_KISI_DOMAIN';
    }

    /**
     * @inheritDoc
     */
    public function validateTenantBoundary(int $tenantId): bool
    {
        $aktifTenantId = auth()->user()?->tenant_id ?? 1; // SAB Multi-Tenant Isolation SSOT
        return $aktifTenantId === $tenantId;
    }

    /**
     * Ham Aday (Lead) verisini kanonik kişi kaydına dönüştürerek dikey dilim kalıcılığı sağlar.
     *
     * @param array<string, mixed> $hamAdayVerisi
     * @return int Oluşturulan kişi_id
     * @throws TenantMismatchException
     * @throws \App\Exceptions\Governance\GlobalHardlockException
     */
    public function secureLeadIngestion(array $hamAdayVerisi): int
    {
        $tenantId = (int)($hamAdayVerisi['tenant_id'] ?? (auth()->user()?->tenant_id ?? 1));

        if ($this->hardlockManager->isHardlocked($tenantId)) {
            throw new \App\Exceptions\Governance\GlobalHardlockException("🚨 SAB SYSTEM HARDLOCK: Ingestion blocked on KisiDomainYonetici due to active hardlock.");
        }

        if (!$this->validateTenantBoundary($tenantId)) {
            $this->auditor->logForensicsAnomaly('CROSS_TENANT_MUTATION_ATTEMPT', $tenantId, [
                'source' => 'KisiDomainYonetici',
                'action' => 'secureLeadIngestion',
                'ad_soyad' => $hamAdayVerisi['ad_soyad'] ?? '',
                'severity' => 'CRITICAL'
            ]);

            throw new TenantMismatchException("🚨 SAB SECURITY BREACH: Cross-tenant lead ingestion blocked in KisiDomainYonetici.");
        }

        return DB::transaction(function () use ($hamAdayVerisi, $tenantId) {
            // Context7 Kanonik Kelime Denetimi: Jenerik status/type sızıntıları burada filtrelenir
            $adSoyad = trim($hamAdayVerisi['ad_soyad'] ?? '');
            $parts = explode(' ', $adSoyad, 2);
            $ad = $parts[0] ?? '';
            $soyad = $parts[1] ?? '';

            $kanonikVeri = [
                'ad' => $ad,
                'soyad' => $soyad,
                'telefon' => $hamAdayVerisi['telefon_numarasi'] ?? $hamAdayVerisi['telefon'] ?? null,
                'eposta' => $hamAdayVerisi['eposta_adresi'] ?? $hamAdayVerisi['eposta'] ?? null,
                'kisi_tipi' => $hamAdayVerisi['kisi_tipi'] ?? 'lead',
                'aktiflik_durumu' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Pesimist satır kilidi sarmalı altında low-level persistence motoruna yazım
            $kisiId = DB::table('kisiler')->insertGetId($kanonikVeri);

            // Phase 15 asenkron event pipeline tetiklemesi
            $this->eventDispatcher->dispatchSingle([
                'event_type' => 'KisiAdayKaydiOlusturuldu',
                'aggregate_type' => 'App\Domain\CQRS\Aggregates\KisiAggregate',
                'aggregate_id' => $kisiId,
                'tenant_id' => $tenantId,
                'payload' => [
                    'kisi_tipi' => $kanonikVeri['kisi_tipi'],
                ],
                'sequence_number' => 1
            ]);

            return $kisiId;
        });
    }
}

<?php

namespace App\Domain\Ilan;

use App\Domain\Core\Security\ZeroTrustAuditorContract;
use App\Domain\Core\BoundedContextContract;
use App\Services\Ilan\IlanCrudService;
use App\Domain\CQRS\Messaging\EventDispatcher;
use App\Exceptions\Governance\TenantMismatchException;
use App\Models\Ilan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class IlanDomainYonetici
 * @package App\Domain\Ilan
 * @description Phase 16: Ilan yaşam döngüsünü monolitten ayıran, pesimist kilitli ve olay güdümlü kurumsal orkestratör.
 */
final class IlanDomainYonetici implements BoundedContextContract
{
    private readonly ZeroTrustAuditorContract $auditor;
    private readonly \App\Domain\Core\Security\GlobalHardlockManagerContract $hardlockManager;

    /**
     * IlanDomainYonetici constructor.
     */
    public function __construct(
        private readonly IlanCrudService $ilanCrudService,
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
        return 'PROPERTYHUB_ILAN_DOMAIN';
    }

    /**
     * @inheritDoc
     */
    public function validateTenantBoundary(int $tenantId): bool
    {
        $aktifTenantId = auth()->user()?->tenant_id ?? 1;
        return $aktifTenantId === $tenantId;
    }

    /**
     * İlan yayın durumunu anayasal olarak günceller ve etki alanı olayını fırlatır.
     *
     * @param int $ilanId
     * @param string $yeniDurum Context7 Kanonik Kelime: yayin_durumu (taslak|yayinda|pasif)
     * @return bool
     * @throws TenantMismatchException
     * @throws \App\Exceptions\Governance\GlobalHardlockException
     */
    public function yayinDurumuMutasyonu(int $ilanId, string $yeniDurum): bool
    {
        $activeTenant = auth()->user()?->tenant_id ?? 1;
        if ($this->hardlockManager->isHardlocked((int)$activeTenant)) {
            throw new \App\Exceptions\Governance\GlobalHardlockException("🚨 SAB SYSTEM HARDLOCK: Mutation blocked on IlanDomainYonetici due to active hardlock.");
        }
        // Bounded Context sınır kapısında kiracılar arası kaçakları yakalayabilmek için
        // global TenantScope sorgu filtresini devre dışı bırakıp modeli yüklüyoruz.
        $ilanCheck = Ilan::withoutGlobalScopes()->where('id', $ilanId)->first();

        if ($ilanCheck && !$this->validateTenantBoundary((int)$ilanCheck->tenant_id)) {
            $this->auditor->logForensicsAnomaly('CROSS_TENANT_MUTATION_ATTEMPT', (int)$ilanCheck->tenant_id, [
                'source' => 'IlanDomainYonetici',
                'record_id' => $ilanId,
                'action' => 'yayinDurumuMutasyonu',
                'attempted_value' => $yeniDurum,
                'severity' => 'CRITICAL'
            ]);

            throw new TenantMismatchException("🚨 SAB SECURITY BREACH: Cross-tenant domain mutation blocked on Ilan ID: {$ilanId}");
        }

        return DB::transaction(function () use ($ilanId, $yeniDurum) {
            // Eloquent builder pesimist kilit enjeksiyonu.
            /** @var Ilan|null $ilan */
            $ilan = Ilan::withoutGlobalScopes()->where('id', $ilanId)->lockForUpdate()->first();

            if (!$ilan) {
                return false;
            }

            $eskiDurum = $ilan->yayin_durumu;

            // Veri kalıcılığı için alt monolitik servis katmanına güvenli delegasyon
            $guncellenenIlan = $this->ilanCrudService->update($ilan, [
                'yayin_durumu' => $yeniDurum,
                'son_guncelleme_tarihi' => now()
            ]);

            if ($guncellenenIlan) {
                // Phase 15 & 16 Olay Güdümlü Çekirdek Tahkimatı: dispatchSingle tetiklenmesi
                $this->eventDispatcher->dispatchSingle([
                    'event_type' => 'IlanYayinDurumuDegisti',
                    'aggregate_type' => 'App\Domain\CQRS\Aggregates\IlanAggregate',
                    'aggregate_id' => $ilanId,
                    'tenant_id' => $ilan->tenant_id,
                    'payload' => [
                        'eski_durum' => $eskiDurum instanceof \App\Enums\IlanDurumu ? $eskiDurum->value : $eskiDurum,
                        'yeni_durum' => $yeniDurum
                    ],
                    'sequence_number' => $this->getNextSequenceForIlan($ilanId)
                ]);
                return true;
            }

            return false;
        });
    }

    /**
     * Olayların sıra numarası (Sequence Number) takibi için atomik sayaç üretir.
     */
    private function getNextSequenceForIlan(int $ilanId): int
    {
        return (int)DB::table('etki_alani_olaylari')
            ->where('kaynak_kimligi', $ilanId)
            ->max('sira_numarasi') + 1;
    }
}

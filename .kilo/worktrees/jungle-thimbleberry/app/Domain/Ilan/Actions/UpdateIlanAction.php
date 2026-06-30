<?php

namespace App\Domain\Ilan\Actions;

use App\Services\Ilan\IlanCrudService;
use App\Domain\CQRS\Messaging\EventDispatcher;
use App\Models\Ilan;
use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\DB;

/**
 * Class UpdateIlanAction
 * @package App\Domain\Ilan\Actions
 * @description CQRS Yazma Yolu: İlan güncelleme komutunu çalıştırır ve fiyat/durum değişim olaylarını fırlatır.
 */
final class UpdateIlanAction
{
    public function __construct(
        private readonly IlanCrudService $ilanCrudService,
        private readonly EventDispatcher $eventDispatcher
    ) {}

    /**
     * Komutu çalıştırır.
     *
     * @param int $tenantId
     * @param int $ilanId
     * @param array $data
     * @return void
     */
    public function execute(int $tenantId, int $ilanId, array $data): void
    {
        DB::transaction(function () use ($tenantId, $ilanId, $data) {
            /** @var Ilan|null $ilan */
            $ilan = Ilan::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('id', $ilanId)
                ->lockForUpdate()
                ->firstOrFail();

            $eskiFiyat = $ilan->fiyat;
            $eskiDurum = $ilan->yayin_durumu;

            $this->ilanCrudService->update($ilan, $data);

            $ilan->refresh();

            // Fiyat değiştiyse olay fırlat
            if (isset($data['fiyat']) && (float)$data['fiyat'] !== (float)$eskiFiyat) {
                $this->eventDispatcher->dispatchSingle([
                    'event_type' => 'IlanFiyatiDegistirildi',
                    'aggregate_type' => 'App\Domain\CQRS\Aggregates\IlanAggregate',
                    'aggregate_id' => $ilanId,
                    'tenant_id' => $tenantId,
                    'payload' => [
                        'eski_fiyat' => $eskiFiyat,
                        'yeni_fiyat' => $ilan->fiyat,
                    ],
                    'sequence_number' => $this->getNextSequence($ilanId)
                ]);
            }

            // Durum değiştiyse olay fırlat
            if (isset($data['yayin_durumu']) && $data['yayin_durumu'] !== $eskiDurum) {
                $this->eventDispatcher->dispatchSingle([
                    'event_type' => 'IlanDurumuDegistirildi',
                    'aggregate_type' => 'App\Domain\CQRS\Aggregates\IlanAggregate',
                    'aggregate_id' => $ilanId,
                    'tenant_id' => $tenantId,
                    'payload' => [
                        'eski_durum' => $eskiDurum instanceof IlanDurumu ? $eskiDurum->value : $eskiDurum,
                        'yeni_durum' => $ilan->yayin_durumu instanceof IlanDurumu ? $ilan->yayin_durumu->value : $ilan->yayin_durumu,
                    ],
                    'sequence_number' => $this->getNextSequence($ilanId)
                ]);
            }
        });
    }

    /**
     * Sıra numarasını getirir.
     *
     * @param int $ilanId
     * @return int
     */
    private function getNextSequence(int $ilanId): int
    {
        return (int)DB::table('etki_alani_olaylari')
            ->where('aggregate_id', $ilanId)
            ->where('aggregate_type', 'App\Domain\CQRS\Aggregates\IlanAggregate')
            ->max('sequence_number') + 1;
    }
}

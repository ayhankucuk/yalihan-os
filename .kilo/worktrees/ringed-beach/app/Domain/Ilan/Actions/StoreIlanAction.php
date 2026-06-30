<?php

namespace App\Domain\Ilan\Actions;

use App\Services\Ilan\IlanCrudService;
use App\Domain\CQRS\Messaging\EventDispatcher;
use App\Models\Ilan;
use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\DB;

/**
 * Class StoreIlanAction
 * @package App\Domain\Ilan\Actions
 * @description CQRS Yazma Yolu: Yeni ilan ekleme komutunu çalıştırır ve IlanOlusturuldu olayını fırlatır.
 */
final class StoreIlanAction
{
    public function __construct(
        private readonly IlanCrudService $ilanCrudService,
        private readonly EventDispatcher $eventDispatcher
    ) {}

    /**
     * Komutu çalıştırır.
     *
     * @param int $tenantId
     * @param array $data
     * @return int
     */
    public function execute(int $tenantId, array $data): int
    {
        return DB::transaction(function () use ($tenantId, $data) {
            $ilanData = array_merge($data, [
                'tenant_id' => $tenantId,
                'danisman_id' => auth('sanctum')->id(),
                'yayin_durumu' => $data['yayin_durumu'] ?? 'taslak',
                'aktiflik_durumu' => 1,
            ]);

            $ilan = $this->ilanCrudService->store($ilanData);

            // CQRS Event Sourcing: Dispatch IlanOlusturuldu event
            $this->eventDispatcher->dispatchSingle([
                'event_type' => 'IlanOlusturuldu',
                'aggregate_type' => 'App\Domain\CQRS\Aggregates\IlanAggregate',
                'aggregate_id' => $ilan->id,
                'tenant_id' => $tenantId,
                'payload' => [
                    'baslik' => $ilan->baslik,
                    'ilan_durumu' => $ilan->yayin_durumu instanceof IlanDurumu ? $ilan->yayin_durumu->value : $ilan->yayin_durumu,
                    'fiyat' => $ilan->fiyat,
                    'ana_kategori_id' => $ilan->ana_kategori_id,
                    'alt_kategori_id' => $ilan->alt_kategori_id,
                    'il' => $ilan->il,
                    'ilce' => $ilan->ilce,
                ],
                'sequence_number' => 1
            ]);

            return $ilan->id;
        });
    }
}

<?php

namespace App\Services\Ilan;

use App\Models\Ilan;
use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\DB;

/**
 * Phase3-WA: Bulk operations are tracked exceptions to single write authority.
 * - Metadata ops (tag, danisman, yayin_tipi): KEEP as bulk — no state machine needed.
 * - yayin_durumu ops (activate/deactivate): TRACKED — bypasses ListingStateMachine.
 *   Future: delegate to YalihanLifecycle for state machine compliance.
 * - Price ops (bulkUpdateFiyat): TRACKED — bypasses price history recording.
 *   Future: delegate per-ilan updates to IlanCrudService::update().
 * - Delete ops: TRACKED — bypasses IlanCrudService::destroy() audit logging.
 */
class IlanBulkService
{
    public function __construct(
        protected IlanCrudService $crudService
    ) {}

    // Phase3-WA: tracked bulk write — bypasses IlanCrudService for metadata-only updates
    // @deprecated QUARANTINE Phase35-BULK REQUIRES_PHASE4: Cannot safely map partial update to IlanCrudService.
    public function bulkUpdate(array $ids, array $updateData): array // QUARANTINE Phase35-BULK
    {
        $updateData = array_filter($updateData, function ($value) {
            return $value !== null && $value !== '';
        });

        if (empty($updateData)) {
            return [
                'success' => false,
                'message' => 'Güncellenecek veri bulunamadı.',
            ];
        }

        $ilanlar = Ilan::whereIn('id', $ids)->get();
        $updatedCount = 0;
        foreach ($ilanlar as $ilan) {
            $this->crudService->update($ilan, $updateData);
            $updatedCount++;
        }

        return [
            'success' => true,
            'message' => $updatedCount.' ilan başarıyla güncellendi.',
            'updated_count' => $updatedCount,
        ];
    }

    // Phase35-BULK: delegated to IlanCrudService for final write authority
    public function bulkDelete(array $ids): array
    {
        $ilanlar = Ilan::whereIn('id', $ids)->get();
        $deletedCount = 0;

        foreach ($ilanlar as $ilan) {
            if ($this->crudService->destroy($ilan)) {
                $deletedCount++;
            }
        }

        return [
            'success' => true,
            'message' => $deletedCount.' ilan başarıyla silindi.',
            'deleted_count' => $deletedCount,
        ];
    }

    public function bulkAction(string $action, array $ids, $value = null): array
    {
        DB::beginTransaction();

        try {
            $affected = 0;
            $message = '';

            switch ($action) {
                case 'activate':
                    $ilanlar = Ilan::whereIn('id', $ids)->get();
                    foreach ($ilanlar as $ilan) {
                        $this->crudService->update($ilan, ['yayin_durumu' => IlanDurumu::YAYINDA->value]);
                        $affected++;
                    }
                    $message = $affected.' ilan aktif yapıldı.';
                    break;

                case 'deactivate':
                    $ilanlar = Ilan::whereIn('id', $ids)->get();
                    foreach ($ilanlar as $ilan) {
                        $this->crudService->update($ilan, ['yayin_durumu' => IlanDurumu::PASIF->value]);
                        $affected++;
                    }
                    $message = $affected.' ilan pasif yapıldı.';
                    break;

                case 'delete':
                    $ilanlar = Ilan::whereIn('id', $ids)->get();
                    foreach ($ilanlar as $ilan) {
                        if ($this->crudService->destroy($ilan)) {
                            $affected++;
                        }
                    }
                    $message = $affected.' ilan silindi.';
                    break;

                case 'assign_danisman':
                    // @deprecated QUARANTINE Phase35-BULK REQUIRES_PHASE4: Cannot safely map partial update to IlanCrudService.
                    if (! $value || ! is_numeric($value)) {
                        return [
                            'success' => false,
                            'message' => 'Danışman seçilmedi.',
                        ];
                    }
                    $ilanlar = Ilan::whereIn('id', $ids)->get();
                    foreach ($ilanlar as $ilan) {
                        $this->crudService->update($ilan, ['danisman_id' => $value]);
                        $affected++;
                    }
                    $message = $affected.' ilana danışman atandı.';
                    break;

                case 'add_tag':
                    if (! $value || ! is_numeric($value)) {
                        return [
                            'success' => false,
                            'message' => 'Etiket seçilmedi.',
                        ];
                    }
                    // ✅ PERFORMANCE FIX: N+1 query önlendi - Bulk attach kullanıldı
                    $ilanlar = Ilan::whereIn('id', $ids)->get();
                    foreach ($ilanlar as $ilan) {
                        $ilan->etiketler()->syncWithoutDetaching([$value]);
                        $affected++;
                    }
                    $message = $affected.' ilana etiket eklendi.';
                    break;

                case 'remove_tag':
                    if (! $value || ! is_numeric($value)) {
                        return [
                            'success' => false,
                            'message' => 'Etiket seçilmedi.',
                        ];
                    }
                    // ✅ PERFORMANCE FIX: N+1 query önlendi - Bulk detach kullanıldı
                    $ilanlar = Ilan::whereIn('id', $ids)->get();
                    foreach ($ilanlar as $ilan) {
                        $ilan->etiketler()->detach([$value]);
                        $affected++;
                    }
                    $message = $affected.' ilandan etiket kaldırıldı.';
                    break;

                default:
                    return [
                        'success' => false,
                        'message' => 'Geçersiz işlem.',
                    ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => $message,
                'affected' => $affected,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \DomainException("Toplu işlem sırasında hata oluştu: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    // @deprecated QUARANTINE Phase35-BULK REQUIRES_PHASE4: Cannot safely map partial update to IlanCrudService.
    public function bulkUpdateYayinTipi(int $userId, array $ilanIds, int $yayinTipiId): array
    {
        return DB::transaction(function () use ($userId, $ilanIds, $yayinTipiId) {
            $ilanlar = \App\Models\Ilan::whereIn('id', $ilanIds)->where('kullanici_id', $userId)->get();
            $updated = 0;
            foreach ($ilanlar as $ilan) {
                $this->crudService->update($ilan, ['yayin_tipi_id' => $yayinTipiId]);
                $updated++;
            }

            return ['updated_count' => $updated, 'message' => "$updated ilan güncellendi"];
        });
    }

    // @deprecated QUARANTINE Phase35-BULK REQUIRES_PHASE4: Bypasses price history. Partial mapping to CrudService is unsafe.
    // Phase3-WA: tracked bulk write — bypasses IlanCrudService price history recording
    public function bulkUpdateFiyat(int $userId, array $ilanIds, float $percentage, string $operation): array
    {
        return DB::transaction(function () use ($userId, $ilanIds, $percentage, $operation) {
            $ilanlar = \App\Models\Ilan::whereIn('id', $ilanIds)
                ->where('kullanici_id', $userId)
                ->get();

            $multiplier = $operation === 'increase'
                ? (1 + $percentage / 100)
                : (1 - $percentage / 100);

            foreach ($ilanlar as $ilan) {
                $this->crudService->update($ilan, ['fiyat' => (int) ($ilan->fiyat * $multiplier)]);
            }

            return [
                'updated_count' => $ilanlar->count(),
                'operation' => $operation,
                'percentage' => $percentage,
                'message' => "{$ilanlar->count()} ilan fiyatı güncellendi",
            ];
        });
    }
}

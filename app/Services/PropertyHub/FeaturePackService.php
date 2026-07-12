<?php

namespace App\Services\PropertyHub;

use App\Models\FeaturePack;
use App\Models\FeaturePackItem;
use App\Models\FeaturePackLog;
use App\Models\Ilan;
use App\Models\Ozellik;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 7.1B — Feature Pack Engine
 *
 * Tek tıkla özellik paketi uygulama servisi.
 * Geri alma (undo) ve replay destekler.
 */
class FeaturePackService
{
    /**
     * Paketi ilan(lar)a uygula
     *
     * @param int $packId
     * @param array<int>|int|null $ilanIds null = tüm kriterlere uyanlar
     * @param int|null $userId
     * @param string|null $reason
     * @return array{applied_count: int, errors: array, log: FeaturePackLog|null}
     */
    public function apply(
        int $packId,
        array|int|null $ilanIds = null,
        ?int $userId = null,
        ?string $reason = null
    ): array {
        $pack = FeaturePack::with('items.ozellik')->findOrFail($packId);
        $items = $pack->items;

        if ($items->isEmpty()) {
            return ['applied_count' => 0, 'errors' => ['Paket boş'], 'log' => null];
        }

        // Hangi ilanlara uygulanacak
        $query = Ilan::query();

        if ($ilanIds !== null) {
            $query->whereIn('id', (array) $ilanIds);
        } elseif (!empty($pack->kategori_ids)) {
            $query->whereIn('alt_kategori_id', $pack->kategori_ids);
        }

        $ilanlar = $query->get();

        $appliedCount = 0;
        $errors = [];
        $allSnapshots = [];

        foreach ($ilanlar as $ilan) {
            $snapshotBefore = $this->getSnapshot($ilan);

            try {
                $this->applyPackToIlan($ilan, $items);
                $appliedCount++;
            } catch (\Throwable $e) {
                $errors[] = "İlan {$ilan->id}: {$e->getMessage()}";
                continue;
            }

            $snapshotAfter = $this->getSnapshot($ilan);
            $allSnapshots[] = [
                'ilan_id' => $ilan->id,
                'before' => $snapshotBefore,
                'after'  => $snapshotAfter,
            ];
        }

        // Audit log
        $log = FeaturePackLog::create([
            'feature_pack_id'  => $pack->id,
            'ilan_id'         => is_int($ilanIds) ? $ilanIds : null,
            'user_id'         => $userId,
            'action'          => 'applied',
            'scope'           => $ilanIds === null ? 'all' : (count((array) $ilanIds) > 1 ? 'bulk' : 'single'),
            'affected_count'  => $appliedCount,
            'snapshot_before'  => $allSnapshots,
            'reason'          => $reason,
        ]);

        // Pack feature_count güncelle
        $pack->updateFeatureCount();

        return [
            'applied_count' => $appliedCount,
            'errors'         => $errors,
            'log'            => $log,
        ];
    }

    /**
     * Bir paketteki özellikleri ilanın feature_assignments tablosuna yaz
     */
    private function applyPackToIlan(Ilan $ilan, Collection $items): void
    {
        foreach ($items as $item) {
            $ozellikId = $item->ozellik_id;
            if (!$ozellikId) continue;

            // Varsılan değer belirle
            $ozellik = $item->ozellik;
            $value = $item->value ?? $this->getDefaultValue($ozellik);

            DB::table('feature_assignments')->updateOrInsert(
                [
                    'feature_id'      => $ozellikId,
                    'assignable_type' => Ilan::class,
                    'assignable_id'   => $ilan->id,
                ],
                [
                    'ozellik_id'        => $ozellikId,
                    'assignable_type'   => Ilan::class,
                    'assignable_id'    => $ilan->id,
                    'main_category_id'  => $ilan->ana_kategori_id,
                    'sub_category_id'  => $ilan->alt_kategori_id,
                    'listing_type_id'  => $ilan->yayin_tipi_id,
                    'source_type'      => 'feature_pack',
                    'field_slug'       => $item->field_slug ?? $ozellik->slug,
                    'value'            => $value,
                    'label_override'    => $ozellik->name,
                    'field_type'       => $ozellik->type ?? 'text',
                    'is_required'      => 0,
                    'is_visible'       => 1,
                    'is_inherited'     => 1,
                    'origin_category_name' => $ilan->kategori ?? 'unknown',
                    'group_name'       => $item->notes ?? null,
                    'display_order'    => $item->display_order ?? 0,
                    'aktiflik_durumu'  => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]
            );
        }
    }

    /**
     * Bir log kaydını geri al (undo)
     */
    public function undo(int $logId, ?int $userId = null): array
    {
        $log = FeaturePackLog::with('pack')->findOrFail($logId);

        if ($log->rolled_back_at !== null) {
            return ['success' => false, 'errors' => ['Zaten geri alınmış']];
        }

        $snapshots = $log->snapshot_before ?? [];
        $rolledBack = 0;
        $errors = [];

        foreach ($snapshots as $snapshot) {
            try {
                $this->restoreSnapshot($snapshot['ilan_id'], $snapshot['before']);
                $rolledBack++;
            } catch (\Throwable $e) {
                $errors[] = "İlan {$snapshot['ilan_id']}: {$e->getMessage()}";
            }
        }

        $log->update([
            'action'         => 'undo_applied',
            'rolled_back_at' => now(),
        ]);

        return [
            'success'       => true,
            'rolled_back'   => $rolledBack,
            'errors'        => $errors,
        ];
    }

    /**
     * İlanın mevcut feature assignment durumunu snapshotla
     */
    private function getSnapshot(Ilan $ilan): array
    {
        return DB::table('feature_assignments')
            ->where('assignable_type', Ilan::class)
            ->where('assignable_id', $ilan->id)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    /**
     * Snapshot'tan restore et
     */
    private function restoreSnapshot(int $ilanId, array $snapshot): void
    {
        // Mevcut assignment'ları temizle
        DB::table('feature_assignments')
            ->where('assignable_type', Ilan::class)
            ->where('assignable_id', $ilanId)
            ->delete();

        // Snapshot'tan geri yaz
        foreach ($snapshot as $row) {
            DB::table('feature_assignments')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Özellik tipine göre varsayılan değer döndür
     */
    private function getDefaultValue(Ozellik $ozellik): string
    {
        return match ($ozellik->type) {
            'boolean' => '1',
            'number'  => '0',
            default   => '',
        };
    }

    /**
     * Paketi oluştur
     */
    public function createPack(array $data): FeaturePack
    {
        return DB::transaction(function () use ($data) {
            $pack = FeaturePack::create([
                'name'             => $data['name'],
                'slug'             => \Illuminate\Support\Str::slug($data['name']),
                'display_name'     => $data['display_name'] ?? $data['name'],
                'description'      => $data['description'] ?? null,
                'icon'             => $data['icon'] ?? null,
                'color'            => $data['color'] ?? null,
                'kategori_ids'     => $data['kategori_ids'] ?? [],
                'yayin_tipi_ids'   => $data['yayin_tipi_ids'] ?? [],
                'aktiflik_durumu'  => $data['aktiflik_durumu'] ?? 1,
                'display_order'    => $data['display_order'] ?? 0,
            ]);

            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $pack->items()->create($item);
                }
                $pack->updateFeatureCount();
            }

            return $pack;
        });
    }

    /**
     * Pakete özellik ekle
     */
    public function addItem(int $packId, array $itemData): FeaturePackItem
    {
        $item = FeaturePackItem::create(array_merge($itemData, [
            'feature_pack_id' => $packId,
        ]));

        FeaturePack::find($packId)?->updateFeatureCount();

        return $item;
    }

    /**
     * Paketi sil (soft değil, gerçek silme)
     */
    public function deletePack(int $packId): bool
    {
        return FeaturePack::findOrFail($packId)->delete() !== false;
    }

    /**
     * Tüm aktif paketleri listele
     */
    public function listAktifPacks(): Collection
    {
        return FeaturePack::with('items.ozellik')
            ->aktif()
            ->ordered()
            ->get();
    }
}

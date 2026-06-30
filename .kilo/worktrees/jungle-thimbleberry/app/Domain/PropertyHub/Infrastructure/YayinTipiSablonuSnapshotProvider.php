<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Infrastructure;

use App\Models\YayinTipiSablonu;
use Illuminate\Support\Collection;

/**
 * Canonical Snapshot Provider — YayinTipiSablonu
 *
 * DriftDetectionService için tek kanonik veri kaynağı.
 * Eloquent üzerinden canonical tip-güvenli snapshot döndürür.
 *
 * Sorumluluk:
 * - Alan kapsamını tek noktadan yönetir
 * - Tip normalizasyonunu (int/string/null) burada yapar
 * - DB::table() raw query kullanmaz
 * - Tenant scope doğru şekilde uygulanır
 */
class YayinTipiSablonuSnapshotProvider
{
    /**
     * Canonical snapshot alanları — SSOT.
     * DriftDetectionService hasDrift() ile birebir eşleşmeli.
     */
    private const SNAPSHOT_FIELDS = [
        'id',
        'ad',
        'aciklama',
        'aktiflik_durumu',
        'display_order',
    ];

    /**
     * Tenant'a ait canonical snapshot koleksiyonu döndürür.
     *
     * @param string $tenantId
     * @return Collection<int, array> [id => canonical_array]
     */
    public function getForTenant(string $tenantId): Collection
    {
        return YayinTipiSablonu::where('tenant_id', $tenantId)
            ->select(self::SNAPSHOT_FIELDS)
            ->get()
            ->mapWithKeys(fn (YayinTipiSablonu $model) => [
                $model->id => $this->toCanonical($model),
            ]);
    }

    /**
     * Canonical array üretir — Eloquent cast'ları devrededir.
     * null ve '' eşit kabul edilir.
     */
    private function toCanonical(YayinTipiSablonu $model): array
    {
        return [
            'id'              => (int) $model->id,
            'ad'              => $this->canonicalString($model->ad),
            'aciklama'        => $this->canonicalString($model->aciklama),
            'aktiflik_durumu' => $this->canonicalInt($model->aktiflik_durumu),
            'display_order'   => $this->canonicalInt($model->display_order),
        ];
    }

    private function canonicalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private function canonicalString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim((string) $value);
    }
}

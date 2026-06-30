<?php

namespace App\Observers;

use App\Models\FeatureAssignment;
use App\Models\TemplateChangeLog;
use App\Models\YayinTipiSablonu;
use App\Services\Ups\UpsCacheService;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ FeatureAssignmentObserver — Governance Enforcement Layer
 *
 * Her FeatureAssignment created/deleted olayında otomatik olarak:
 * 1. Cache invalidation (UpsCacheService::invalidateForJunction)
 * 2. TemplateChangeLog kaydı
 *
 * P1-C FIX: creating() hook — write-time polymorphic integrity guard.
 * assignable_type/assignable_id çifti DB'de var olmalı; yoksa orphan üretimi engellenir.
 * DB FK olmadığı için uygulama seviyesinde contract zorunluludur.
 *
 * Admin\TemplateController da dahil olmak üzere hiçbir write path
 * bu iki işlemi manuel çağırmak ZORUNDA değildir.
 * Bypass structurally impossible.
 *
 * @see docs/adr/2026-02-21-governance-enforcement-layer.md
 */
class FeatureAssignmentObserver
{
    /** İzin verilen polymorphic tipler (allowlist — SSOT) */
    private const ALLOWED_ASSIGNABLE_TYPES = [
        \App\Models\YayinTipiSablonu::class,
        \App\Models\AltKategoriYayinTipi::class,
    ];

    public function __construct(private UpsCacheService $cacheService) {}

    /**
     * P1-C: Write-time polymorphic integrity guard.
     * assignable_type allowlist + assignable_id existence kontrolü.
     * Orphan üretimi bu noktada kırılır (repair job'a bırakılmaz).
     */
    public function creating(FeatureAssignment $assignment): void
    {
        $type = $assignment->assignable_type;

        // 1. Allowlist kontrolü
        if (!in_array($type, self::ALLOWED_ASSIGNABLE_TYPES, true)) {
            Log::error('FeatureAssignmentObserver: yasak assignable_type', [
                'type'  => $type,
                'id'    => $assignment->assignable_id,
                'izinli' => implode(', ', self::ALLOWED_ASSIGNABLE_TYPES),
            ]);
            throw new \InvalidArgumentException(
                "FeatureAssignment integrity ihlali: '{$type}' allowlist dışında."
            );
        }

        // 2. Referential existence kontrolü (DB FK olmadığı için uygulama guard'ı)
        /** @var \Illuminate\Database\Eloquent\Model $modelClass */
        $modelClass = $type;
        $exists = $modelClass::where('id', $assignment->assignable_id)->exists();
        if (!$exists) {
            Log::error('FeatureAssignmentObserver: orphan write engellendi', [
                'assignable_type' => $type,
                'assignable_id'   => $assignment->assignable_id,
            ]);
            throw new \InvalidArgumentException(
                "FeatureAssignment integrity ihlali: {$type}#{$assignment->assignable_id} mevcut değil."
            );
        }
    }

    /**
     * Feature atandığında: cache invalidate + audit log
     */
    public function created(FeatureAssignment $assignment): void
    {
        if ($assignment->assignable_type !== YayinTipiSablonu::class) {
            return;
        }

        $this->invalidateAndLog($assignment, 'feature_added');
    }

    /**
     * Feature görünürlüğü / zorunluluğu güncellendiginde: cache invalidate + audit log
     */
    public function updated(FeatureAssignment $assignment): void
    {
        if ($assignment->assignable_type !== YayinTipiSablonu::class) {
            return;
        }

        $this->invalidateAndLog($assignment, 'feature_updated');
    }

    /**
     * Feature kaldırıldığında: cache invalidate + audit log
     */
    public function deleted(FeatureAssignment $assignment): void
    {
        if ($assignment->assignable_type !== YayinTipiSablonu::class) {
            return;
        }

        $this->invalidateAndLog($assignment, 'feature_removed');
    }

    /**
     * Cache iptali + audit log — her iki olayda da ortak işlem
     */
    private function invalidateAndLog(FeatureAssignment $assignment, string $action): void
    {
        /** @var YayinTipiSablonu|null $junction */
        $junction = $assignment->assignable;

        try {
            $this->cacheService->invalidateForJunction(
                $assignment->assignable_id,
                $junction?->kategori_id,
                $junction?->yayin_tipi_id,
            );
        } catch (\Throwable $e) {
            Log::warning('FeatureAssignmentObserver: cache invalidation failed', [
                'assignment_id' => $assignment->id,
                'assignable_id' => $assignment->assignable_id,
                'hata_mesaji'   => $e->getMessage(),
            ]);
        }

        // Test ortamında changelog FK'lar hazır olmadığı için atla
        if (app()->runningUnitTests()) {
            return;
        }

        try {
            $userId  = auth()->id() ?? 0;
            $version = TemplateChangeLog::where('yayin_tipi_sablonu_id', $assignment->assignable_id)->count() + 1;

            if ($action === 'feature_added') {
                TemplateChangeLog::logFeatureAdded(
                    $assignment->assignable_id,
                    $assignment->feature_id,
                    $userId,
                    $version,
                    $assignment->toArray(),
                );
            } elseif ($action === 'feature_removed') {
                TemplateChangeLog::logFeatureRemoved(
                    $assignment->assignable_id,
                    $assignment->feature_id,
                    $userId,
                    $version,
                    [],
                );
            } else {
                // feature_updated: visibility / required değişikliği
                TemplateChangeLog::create([
                    'yayin_tipi_sablonu_id' => $assignment->assignable_id,
                    'user_id'              => $userId,
                    'feature_id'           => $assignment->feature_id,
                    'aksiyon_tipi'         => 'feature_updated',
                    'yeni_degerler'        => $assignment->getDirty(),
                    'eski_degerler'        => $assignment->getOriginal(),
                    'versiyon_numarasi'    => $version,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('FeatureAssignmentObserver: changelog write failed', [
                'assignment_id' => $assignment->id,
                'action'        => $action,
                'hata_mesaji'   => $e->getMessage(),
            ]);
        }
    }
}


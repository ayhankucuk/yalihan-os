<?php

namespace App\Domain\PropertyHub\Services;

use App\Models\UpsTemplate;
use App\Models\YayinTipiSablonu;
use App\Domain\PropertyHub\ValueObjects\SealedTemplateJson;
use Illuminate\Support\Facades\DB;

/**
 * TemplateSealingPolicy — Domain Service
 *
 * [SAB ENFORCEMENT]: Aggregate Overgrowth Prevention
 * Canonical JSON, hashing, versioning ve sealing policy'sini
 * kapsulleyen domain service.
 *
 * Sorumluluklar:
 * - Template JSON validation + canonicalization (SealedTemplateJson VO uzerinden)
 * - Duplicate detection (hash karsilastirmasi)
 * - Version yonetimi (deactivate old, create new)
 * - Junction guncelleme
 */
class TemplateSealingPolicy
{
    /**
     * Seal (store) a new UPS Template version
     *
     * @return array{template: UpsTemplate, is_duplicate: bool}
     * @throws \Exception
     */
    public function seal(int $junctionId, array $upsJson, bool $shouldSeal = true, ?int $userId = null): array
    {
        // Value Object handles validation, canonicalization, hashing
        $sealed = new SealedTemplateJson($upsJson);

        $junction = YayinTipiSablonu::lockForUpdate()->findOrFail($junctionId);

        // Duplicate check via Value Object
        // ORDER BY id DESC → deterministic: newest active wins if invariant violated
        $currentActive = UpsTemplate::where('yayin_tipi_sablonu_id', $junctionId)
            ->where('aktiflik_durumu', 1)
            ->orderBy('id', 'desc')
            ->first();

        if ($currentActive && $sealed->equalsHash($currentActive->template_hash)) {
            return ['template' => $currentActive, 'is_duplicate' => true];
        }

        // New version
        $lastVersion = UpsTemplate::where('yayin_tipi_sablonu_id', $junctionId)
            ->max('template_version') ?? 0;

        // Deactivate old (DB::table bypass sealed guard — intentional; only aktiflik_durumu changes)
        // active_junction_id = NULL releases the UNIQUE slot
        UpsTemplate::where('yayin_tipi_sablonu_id', $junctionId)
            ->update([
                'aktiflik_durumu' => 0,
                'active_junction_id' => null,
            ]);

        $newTemplate = UpsTemplate::create([
            'yayin_tipi_sablonu_id' => $junctionId,
            'kategori_id' => $junction->kategori_id,
            'yayin_tipi_id' => $junction->yayin_tipi_id ?? 0,
            'template_json' => $sealed->toArray(),
            'template_version' => $lastVersion + 1,
            'template_hash' => $sealed->hash(),
            'sealed_at' => $shouldSeal ? now() : null,
            'sealed_by_user_id' => $userId,
            'aktiflik_durumu' => 1,
            'active_junction_id' => $junctionId, // Claims the UNIQUE slot
        ]);

        // Update junction
        $junction->update(['ups_template_id' => $newTemplate->id]);

        return ['template' => $newTemplate, 'is_duplicate' => false];
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Intelligence;

use App\Models\PropertyConfigVersion;
use App\Models\IlanKategori;
use App\Models\UpsTemplate;
use Illuminate\Support\Collection;

/**
 * Class DraftImpactSimulator
 *
 * Predicts health matrix shifts for a draft version.
 */
class DraftImpactSimulator
{
    /**
     * Simulate health matrix impact for a specific tenant.
     */
    public function simulate(PropertyConfigVersion $version): array
    {
        $tenantId = $version->tenant_id ?? 'SYSTEM';
        $snapshot = $version->snapshot_json ?? [];
        $governedTemplates = collect($snapshot['templates'] ?? [])->groupBy(fn($t) => $t['kategori_id'] . '-' . $t['yayin_tipi_id']);

        // Get Live State (Scoped by tenant)
        $allTemplates = UpsTemplate::where('tenant_id', $tenantId)
            ->active()
            ->get()
            ->groupBy(fn($t) => $t['kategori_id'] . '-' . $t['yayin_tipi_id']);

        $stats = [
            'healthy' => 0,
            'drift' => 0,
            'shadow' => 0,
            'missing' => 0,
            'total' => 0,
        ];

        // We simulate based on Category x PublishType combinations (Scoped by tenant)
        $categories = IlanKategori::where('tenant_id', $tenantId)
            ->with('yayinTipleri')
            ->get();

        foreach ($categories as $category) {
            foreach ($category->yayinTipleri as $yayinTipi) {
                $stats['total']++;
                $lookupKey = $category->id . '-' . $yayinTipi->yayin_tipi_id;

                $liveTemplate = $allTemplates->get($lookupKey)?->first();
                $govTemplate = $governedTemplates->get($lookupKey)?->first();

                if (!$liveTemplate && !$govTemplate) {
                    $stats['missing']++;
                } elseif ($govTemplate && !$liveTemplate) {
                    $stats['shadow']++;
                } elseif ($liveTemplate && !$govTemplate) {
                    $stats['drift']++;
                } else {
                    // Content check
                    if ($this->hasContentDrift($liveTemplate, $govTemplate)) {
                        $stats['drift']++;
                    } else {
                        $stats['healthy']++;
                    }
                }
            }
        }

        $healthScore = $stats['total'] > 0 ? (int)(($stats['healthy'] / $stats['total']) * 100) : 0;

        return [
            'stats' => $stats,
            'predicted_health_score' => $healthScore,
            'affected_nodes_count' => $stats['shadow'] + $stats['drift'],
        ];
    }

    private function hasContentDrift($live, $gov): bool
    {
        if ($live->name !== $gov['name']) return true;

        $liveData = is_string($live->template_json) ? json_decode($live->template_json, true) : $live->template_json;
        $govData = $gov['template_json'] ?? null;

        return $liveData != $govData;
    }
}

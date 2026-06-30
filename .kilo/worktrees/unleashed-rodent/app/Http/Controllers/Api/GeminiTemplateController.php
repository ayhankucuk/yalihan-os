<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

class GeminiTemplateController extends Controller
{
    public function enrich(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer|min:1',
            'yayin_tipi_id' => 'required|integer|min:1',
            'partial_listing' => 'nullable|array',
        ]);

        $kategoriId = (int) $validated['kategori_id'];
        $yayinTipiId = (int) $validated['yayin_tipi_id'];
        $partialListing = $validated['partial_listing'] ?? [];

        // Read-only query: template assignments for Gemini enrichment
        $template = YayinTipiSablonu::findOrFail($yayinTipiId);

        $assignments = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $template->id)
            ->with(['feature' => function($q) {
                $q->with('category')->active(); // context7-ignore
            }])
            ->ordered() // context7-ignore
            ->get();

        $tier1 = ['ana_baslik', 'metrekare', 'fiyat', 'il', 'oda_sayisi'];
        $tier2 = ['mahalle', 'donem', 'balkon_teras', 'asansor', 'bir_oezet'];
        $tier3 = ['havuz', 'sauna', 'spor_salonu'];

        $requiredSlugs = $assignments
            ->filter(fn ($assignment) => $assignment->is_required && $assignment->feature)
            ->map(fn ($assignment) => $assignment->feature->slug)
            ->filter()
            ->values();

        $missingTier1 = collect($tier1)
            ->filter(fn ($slug) => !$this->hasValue($partialListing, $slug))
            ->values();

        $autoFillCandidates = collect($tier1)
            ->merge($tier2)
            ->filter(fn ($slug) => !$this->hasValue($partialListing, $slug))
            ->values();

        $kategori = IlanKategori::find($kategoriId);

        return ResponseService::success([
            'template_id' => $template->id,
            'kategori_id' => $kategoriId,
            'yayin_tipi_id' => $yayinTipiId,
            'kategori_name' => $kategori?->name,
            'feature_count' => $assignments->count(),
            'required_slugs' => $requiredSlugs,
            'missing_tier1' => $missingTier1,
            'auto_fill_candidates' => $autoFillCandidates,
            'tiers' => [
                'tier1' => $tier1,
                'tier2' => $tier2,
                'tier3' => $tier3,
            ],
            'guidance' => [
                'description' => 'Gemini doldurma sırası: Tier1 → Tier2 → Tier3. Tier1 boş geçilemez, Tier2 kaliteyi ve SEO skorunu artırır.',
                'pricing' => 'Fiyat alanı piyasa ortalamasına göre outlier kontrolüne tabi tutulur (±30% uyarı).',
                'seo' => 'Bir özet alanı 200-400 karakter olmalı, konum + özellik + fayda içermeli.',
                'roi' => 'Arsa/KAKS senaryolarında ROI hesaplaması için fiyat, metrekare, kaks, imar_durumu zorunlu.',
            ],
        ]);
    }

    private function hasValue(array $payload, string $key): bool
    {
        if (!array_key_exists($key, $payload)) {
            return false;
        }

        $value = $payload[$key];
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }
}

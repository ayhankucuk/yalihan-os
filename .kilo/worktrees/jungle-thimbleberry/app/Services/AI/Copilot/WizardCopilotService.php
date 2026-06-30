<?php

namespace App\Services\AI\Copilot;

use App\Models\IlanKategori;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * §6 Smart Wizard Intelligence
 *
 * Category→field generation, template coupling analysis,
 * geo/polygon mode detection, partial save validation, AI assistance hooks.
 */
class WizardCopilotService
{
    /**
     * Analyze wizard state and return intelligence.
     */
    public function analyze(array $wizardData): array
    {
        try {
            return [
                'template_coupling' => $this->analyzeTemplateCoupling($wizardData),
                'field_generation' => $this->analyzeFieldGeneration($wizardData),
                'geo_mode' => $this->detectGeoMode($wizardData),
                'publish_readiness' => $this->assessPublishReadiness($wizardData),
                'ai_hooks' => $this->suggestAiHooks($wizardData),
            ];
        } catch (\Exception $e) {
            Log::warning('WizardCopilotService analysis failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * §6.1 Template coupling analysis
     * Checks if category→yayin tipi→template→features chain is complete.
     */
    protected function analyzeTemplateCoupling(array $data): array
    {
        $result = [
            'chain_complete' => false,
            'missing_step' => null,
            'template_id' => null,
            'feature_count' => 0,
        ];

        $kategoriId = $data['ana_kategori_id'] ?? null;
        $altKategoriId = $data['alt_kategori_id'] ?? null;
        $yayinTipiId = $data['yayin_tipi_id'] ?? null;

        if (empty($kategoriId)) {
            $result['missing_step'] = 'kategori';
            return $result;
        }

        if (empty($yayinTipiId)) {
            // Check if publication types exist for this category chain
            $yayinTipleri = IlanKategori::where('seviye', 2)
                ->where(function ($q) use ($kategoriId, $altKategoriId) {
                    if ($altKategoriId) {
                        $q->where('parent_id', $altKategoriId);
                    } else {
                        $q->where('parent_id', $kategoriId);
                        $q->orWhereIn('parent_id', function ($sub) use ($kategoriId) {
                            $sub->select('id')
                                ->from('ilan_kategorileri')
                                ->where('parent_id', $kategoriId)
                                ->where('seviye', 1);
                        });
                    }
                })
                ->count();

            if ($yayinTipleri === 0) {
                $result['missing_step'] = 'yayin_tipi_tanimsiz';
            } else {
                $result['missing_step'] = 'yayin_tipi_secilmedi';
            }
            return $result;
        }

        // Check template existence
        $template = DB::table('yayin_tipi_sablonlari')
            ->where('ilan_kategorisi_id', $yayinTipiId)
            ->where('aktiflik_durumu', 1)
            ->first();

        if (!$template) {
            $result['missing_step'] = 'sablon_yok';
            return $result;
        }

        $result['template_id'] = $template->id;

        // Count feature assignments
        $featureCount = DB::table('feature_assignments')
            ->where('assignable_type', 'App\\Models\\YayinTipiSablonu')
            ->where('assignable_id', $template->id)
            ->count();

        $result['feature_count'] = $featureCount;

        if ($featureCount === 0) {
            $result['missing_step'] = 'ozellik_atanmamis';
            return $result;
        }

        $result['chain_complete'] = true;
        return $result;
    }

    /**
     * §6.2 Field generation analysis
     * Returns which fields should be generated for the selected category chain.
     */
    protected function analyzeFieldGeneration(array $data): array
    {
        $yayinTipiId = $data['yayin_tipi_id'] ?? null;
        if (empty($yayinTipiId)) {
            return ['fields' => [], 'count' => 0];
        }

        try {
            $fields = DB::table('feature_assignments as fa')
                ->join('features as f', 'f.id', '=', 'fa.feature_id')
                ->join('yayin_tipi_sablonlari as s', function ($join) {
                    $join->on('s.id', '=', 'fa.assignable_id')
                        ->where('fa.assignable_type', '=', 'App\\Models\\YayinTipiSablonu');
                })
                ->where('s.ilan_kategorisi_id', $yayinTipiId)
                ->where('s.aktiflik_durumu', 1)
                ->where('f.aktiflik_durumu', 1)
                ->select('f.id', 'f.anahtar', 'f.baslik', 'f.veri_tipi', 'fa.is_required', 'fa.display_order')
                ->orderBy('fa.display_order')
                ->get()
                ->toArray();

            return [
                'fields' => $fields,
                'count' => count($fields),
                'required_count' => count(array_filter($fields, fn($f) => $f->is_required ?? false)),
            ];
        } catch (\Exception $e) {
            Log::warning('WizardCopilotService field generation failed', ['error' => $e->getMessage()]);
            return ['fields' => [], 'count' => 0];
        }
    }

    /**
     * §6.3 Geo/Polygon mode detection
     * Returns whether this category should show polygon tools.
     */
    protected function detectGeoMode(array $data): array
    {
        $kategoriId = $data['ana_kategori_id'] ?? $data['alt_kategori_id'] ?? null;

        $geoMode = [
            'show_map' => true,
            'show_polygon' => false,
            'polygon_required' => false,
            'reason' => null,
        ];

        if (empty($kategoriId)) {
            return $geoMode;
        }

        try {
            $kategori = IlanKategori::find($kategoriId);
            if (!$kategori) {
                return $geoMode;
            }

            $kategoriAdi = mb_strtolower($kategori->name ?? '');
            $arsaKeywords = ['arsa', 'arazi', 'tarla', 'zeytinlik', 'bağ', 'bahçe'];

            foreach ($arsaKeywords as $keyword) {
                if (str_contains($kategoriAdi, $keyword)) {
                    $geoMode['show_polygon'] = true;
                    $geoMode['polygon_required'] = false; // Recommended, not required
                    $geoMode['reason'] = $kategoriAdi . ' tipi için parsel çizimi önerilir.';
                    break;
                }
            }
        } catch (\Exception $e) {
            Log::warning('WizardCopilotService geo mode detection failed', ['error' => $e->getMessage()]);
        }

        return $geoMode;
    }

    /**
     * §9.2 Publish readiness from wizard perspective
     */
    protected function assessPublishReadiness(array $data): array
    {
        $blockers = [];
        $warnings = [];

        // Hard blockers (cannot publish without these)
        if (empty($data['baslik'])) {
            $blockers[] = ['field' => 'baslik', 'label' => 'Başlık'];
        }
        if (empty($data['fiyat'])) {
            $blockers[] = ['field' => 'fiyat', 'label' => 'Fiyat'];
        }
        if (empty($data['ana_kategori_id'])) {
            $blockers[] = ['field' => 'ana_kategori_id', 'label' => 'Kategori'];
        }

        // Soft warnings (can publish but quality suffers)
        if (empty($data['aciklama']) || mb_strlen($data['aciklama'] ?? '') < 150) {
            $warnings[] = ['field' => 'aciklama', 'label' => 'Açıklama (min 150 karakter)'];
        }
        if (empty($data['il_id'])) {
            $warnings[] = ['field' => 'il_id', 'label' => 'İl'];
        }
        if (empty($data['lat']) || empty($data['lng'])) {
            $warnings[] = ['field' => 'lat', 'label' => 'Harita koordinatı'];
        }

        return [
            'ready' => empty($blockers),
            'blocker_count' => count($blockers),
            'warning_count' => count($warnings),
            'blockers' => $blockers,
            'warnings' => $warnings,
            'completeness' => round((1 - (count($blockers) / 3)) * 100),
        ];
    }

    /**
     * §6.4 AI assistance hook suggestions
     */
    protected function suggestAiHooks(array $data): array
    {
        $hooks = [];

        // AI title suggestion
        if (!empty($data['ana_kategori_id']) && empty($data['baslik'])) {
            $hooks[] = [
                'type' => 'ai_title',
                'label' => 'AI Başlık Öner',
                'description' => 'Kategori ve özelliklere göre SEO uyumlu başlık üretin.',
                'available' => true,
            ];
        }

        // AI description generation
        if (!empty($data['baslik']) && (empty($data['aciklama']) || mb_strlen($data['aciklama'] ?? '') < 50)) {
            $hooks[] = [
                'type' => 'ai_description',
                'label' => 'AI Açıklama Oluştur',
                'description' => 'Başlık ve özelliklerden profesyonel açıklama üretin.',
                'available' => true,
            ];
        }

        // AI price suggestion
        if (!empty($data['il_id']) && !empty($data['ana_kategori_id']) && empty($data['fiyat'])) {
            $hooks[] = [
                'type' => 'ai_price',
                'label' => 'AI Fiyat Öner',
                'description' => 'Bölge ve özelliklere göre pazar fiyat aralığı önerileri.',
                'available' => true,
            ];
        }

        return $hooks;
    }
}

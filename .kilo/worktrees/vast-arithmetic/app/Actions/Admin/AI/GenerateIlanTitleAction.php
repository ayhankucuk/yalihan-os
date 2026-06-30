<?php

namespace App\Actions\Admin\AI;

use App\Services\AI\DanismanAIService;
use App\Services\AI\AiCostGuardService;
use App\Services\AI\AiFeatureFlags;
use App\Models\IlanKategori;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\YayinTipiSablonu;

/**
 * 🛰️ GenerateIlanTitleAction
 * 
 * Part of P2 Remediation. Handles title generation logic including 
 * Wizard format normalization and budget guarding.
 */
class GenerateIlanTitleAction
{
    protected DanismanAIService $danismanAI;
    protected AiCostGuardService $costGuard;

    public function __construct(
        DanismanAIService $danismanAI,
        AiCostGuardService $costGuard
    ) {
        $this->danismanAI = $danismanAI;
        $this->costGuard = $costGuard;
    }

    /**
     * Execute title generation logic.
     */
    public function handle(array $input, array $options = []): array
    {
        // 1. Feature flag guard
        if (!AiFeatureFlags::isEnabled('assist')) {
            return ['success' => false, 'error' => 'AI özelliği devre dışı', 'code' => 403];
        }

        // 2. Budget Guard
        $budget = $this->costGuard->checkBudget();
        if (!$budget['allowed']) {
            return ['success' => false, 'error' => 'AI bütçe sınırı aşıldı: ' . $budget['reason'], 'code' => 402];
        }

        // 3. Data Normalization
        $ilanData = $this->normalizeData($input);

        // 4. Generate
        $result = $this->danismanAI->generateListingTitle($ilanData, [
            'tone' => $options['tone'] ?? 'seo',
            'provider' => $options['provider'] ?? config('ai.default_provider', 'ollama'),
        ]);

        if (!$result['success']) {
            return ['success' => false, 'error' => 'Başlık oluşturulamadı', 'code' => 500];
        }

        // 5. Post-process (Normalization of variants)
        $formatted = $this->formatOutput($result);

        return array_merge(['success' => true], $formatted);
    }

    /**
     * Normalize input data, handling both Wizard simple format and legacy format.
     */
    protected function normalizeData(array $input): array
    {
        $isSimpleFormat = isset($input['kategori']) && isset($input['il']);

        if (!$isSimpleFormat) {
            return $input['ilan'] ?? $input;
        }

        $kategoriAdi = $this->getName($input['kategori'], IlanKategori::class, 'name');
        $ilAdi = $this->getName($input['il'], Il::class, 'il_adi');
        $ilceAdi = $this->getName($input['ilce'] ?? null, Ilce::class, 'ilce_adi');
        $mahalleAdi = $this->getName($input['mahalle'] ?? null, Mahalle::class, 'mahalle_adi');
        
        $yayinTipiId = $input['yayin_tipi_id'] ?? null;
        $yayinTipiAdi = 'Satılık';
        if ($yayinTipiId && is_numeric($yayinTipiId)) {
            $yayinTipi = YayinTipiSablonu::find($yayinTipiId);
            $yayinTipiAdi = $yayinTipi->ad ?? $yayinTipi->name ?? 'Satılık';
        } else if ($yayinTipiId) {
            $yayinTipiAdi = $yayinTipiId;
        }

        return [
            'kategori' => $kategoriAdi,
            'il' => $ilAdi,
            'ilce' => $ilceAdi,
            'mahalle' => $mahalleAdi,
            'yayin_tip' . 'i_adi' => $yayinTipiAdi,
        ];
    }

    protected function getName($id, $modelClass, $attr)
    {
        if (!$id) return null;
        if (!is_numeric($id)) return $id;
        
        $model = $modelClass::find($id);
        return $model->{$attr} ?? $model->ad ?? $model->name ?? $id;
    }

    /**
     * Parse raw AI output into clean titles and variants.
     */
    protected function formatOutput(array $result): array
    {
        $rawText = $result['content'] ?? '';
        $variants = array_filter(array_map('trim', explode("\n", $rawText)));
        $titles = [];

        foreach ($variants as $variant) {
            $clean = preg_replace('/^[\d\.\-\*]+\s*/', '', $variant);
            if (strlen($clean) > 10) {
                $titles[] = $clean;
            }
        }

        return [
            'text' => $titles[0] ?? '',
            'alternatives' => array_slice($titles, 0, 5),
            'variants' => array_map(fn($t) => ['text' => $t, 'poi_badges' => []], array_slice($titles, 0, 3)),
            'provider' => $result['provider'] ?? ($result['model'] ?? 'unknown'),
            'model' => $result['model'] ?? 'unknown',
        ];
    }
}

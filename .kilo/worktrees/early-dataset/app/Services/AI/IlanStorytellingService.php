<?php

namespace App\Services\AI;

use App\Models\Ilan;
use App\Models\IlanMetin;
use App\Services\IlanVisionService;
use App\Services\AI\YalihanCortex;
use App\Services\Logging\LogService;

class IlanStorytellingService
{
    public function __construct(
        protected YalihanCortex $cortex,
        protected IlanVisionService $visionService,
        protected \App\Services\Template\TemplateService $templateService
    ) {}

    /**
     * İlan için duygusal metin oluştur
     * Context7-Hybrid: taslak, aktif, yapay_zeka
     */
    public function olustur(int $ilanId, string $ton = 'profesyonel'): IlanMetin
    {
        $ilan = Ilan::with(['kategori', 'yayinTipi', 'il', 'ilce', 'fotograflar'])->findOrFail($ilanId);

        // 0. Fetch Template Hints
        $hints = [];
        $kategoriId = $ilan->yayinTipi?->kategori_id ?? ($ilan->ana_kategori_id ?? $ilan->alt_kategori_id);

        if ($kategoriId && $ilan->yayin_tipi_id) {
            $templateResult = $this->templateService->autoSelectTemplate($kategoriId, $ilan->yayin_tipi_id);
            $hints = $templateResult['template']['storytelling_hints'] ?? [];
        }

        // 1. ✨ V3: Vision Engine verilerini on-the-fly üret (Table-less architecture)
        $visionData = $this->visionService->getOptimizedSequence($ilan)
            ->map(fn($v) => [
                'oda' => $v['oda_tipi'],
                'kalite' => $v['puani'],
                'etiketler' => [] // Placeholder for dynamic tags
            ]);

        // 2. Kaynak verileri hazırla
        $kaynaklar = [
            'kategori' => $ilan->kategori->baslik ?? 'Konut',
            'yayin_tipi' => $ilan->yayinTipi->name ?? 'Satılık',
            'lokasyon' => ($ilan->il->il_adi ?? '') . ' / ' . ($ilan->ilce->ilce_adi ?? ''),
            'vision_insights' => $visionData->toArray(),
            'ozellikler' => $ilan->ozellikler ?? []
        ];

        // 3. AI Prompt oluştur
        $prompt = $this->buildPrompt($ilan, $kaynaklar, $ton, $hints);

        // 4. Cortex'e gönder
        $cortexResponse = $this->cortex->generateIlanDescription($ilan, [
            'prompt_override' => $prompt,
            'tone' => $ton
        ]);

        $aiResponse = $cortexResponse['text'] ?? $cortexResponse['description'] ?? '';

        // 5. Metni kaydet
        $metin = IlanMetin::create([
            'ilan_id' => $ilanId,
            'baslik' => $this->extractBaslik($aiResponse),
            'aciklama' => $aiResponse,
            'ton' => $ton,
            'taslak_durumu' => true,
            'aktiflik_durumu' => false,
            'yapay_zeka_durumu' => true,
            'kaynak_veriler' => $kaynaklar
        ]);

        LogService::info('Storytelling AI: Metin oluşturuldu', [
            'ilan_id' => $ilanId,
            'metin_id' => $metin->id,
            'ton' => $ton
        ]);

        return $metin;
    }

    protected function buildPrompt(Ilan $ilan, array $kaynaklar, string $ton, array $hints = []): string
    {
        $visionInsights = collect($kaynaklar['vision_insights'])
            ->map(fn($v) => "{$v['oda']} (Kalite: {$v['kalite']}/10)")
            ->join(', ');

        $vurgular = !empty($hints) ? "\n**Vurgulanması Gereken Noktalar:**\n- " . implode("\n- ", $hints) . "\n" : "";

        return <<<PROMPT
Sen profesyonel bir emlak metni yazarısın. Aşağıdaki bilgileri kullanarak duygusal ve çekici bir ilan metni yaz.

**İlan Bilgileri:**
- Kategori: {$kaynaklar['kategori']}
- Yayın Tipi: {$kaynaklar['yayin_tipi']}
- Lokasyon: {$kaynaklar['lokasyon']}
- Vision AI Analizi: {$visionInsights}

**Ton:** {$ton}
{$vurgular}
**Kurallar:**
- Samimi ve doğal dil kullan
- Vision AI'nın tespit ettiği özelliklerden bahset
- Hayal kurmaya teşvik et
- Abartma, gerçekçi kal
- 150-200 kelime arası

Metin:
PROMPT;
    }

    protected function extractBaslik(string $text): string
    {
        $lines = explode("\n", trim($text));
        return mb_substr($lines[0], 0, 100);
    }
}

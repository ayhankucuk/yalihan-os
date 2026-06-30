<?php

namespace App\Services\Cortex;

use App\Models\Ilan;
use App\Services\AIService;
use Illuminate\Support\Facades\Log;

class CortexPitchGenerator
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate a pitch for a specific listing and channel
     *
     * @param Ilan $ilan
     * @param string $channel telegram|whatsapp|email|sms
     * @return array
     */
    public function generatePitch(Ilan $ilan, string $channel = 'telegram'): array
    {
        // 1. Prepare Context from Listing
        $context = $this->prepareContext($ilan, $channel);

        // 2. Build Prompt
        $prompt = $this->buildPrompt($channel, $context);

        // 3. Generate via AI Service
        try {
            $response = $this->aiService->generate($prompt, ['temperature' => 0.7]);
            
            // Handle response format variations from AI Service
            $content = $response['data'] ?? $response; 
            if (is_array($content) && isset($content['value'])) {
                $content = $content['value'];
            }
            
            // Validate content
            if (empty($content) || !is_string($content)) {
                throw new \Exception("AI returned empty content");
            }

            return [
                'success' => true,
                'content' => $content,
                'channel' => $channel,
                'meta' => $response['metadata'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::warning("CortexPitchGenerator AI Failed, using fallback: " . $e->getMessage());
            
            // Fallback generation
            $fallbackContent = $this->generateFallbackPitch($ilan, $context, $channel);
            
            return [
                'success' => true, // Successfully returned a pitch (fallback)
                'content' => $fallbackContent,
                'channel' => $channel,
                'meta' => ['provider' => 'fallback_template', 'error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Generate a template-based fallback pitch
     */
    private function generateFallbackPitch(Ilan $ilan, array $context, string $channel): string
    {
        $roi = $context['cortex_analysis']['roi_score'];
        $yield = $context['cortex_analysis']['annual_yield'];
        $emoji = $channel === 'telegram' ? "🔥" : "";
        
        switch ($channel) {
            case 'telegram':
                return "🚨 *CORTEX FIRSAT ALARMI* {$emoji}\n\n" .
                       "🏠 *{$ilan->baslik}*\n" .
                       "📍 {$context['listing']['location']}\n\n" .
                       "📈 *YATIRIM ANALİZİ:*\n" .
                       "• ROI Skoru: %{$roi}\n" .
                       "• Yıllık Getiri: {$yield}\n\n" .
                       "💡 _Cortex AI bu mülkü yüksek getiri potansiyeli nedeniyle öneriyor._\n\n" .
                       "📞 Detaylar için iletişime geçin!";
            
            case 'whatsapp':
                return "Merhaba, yatırım portföyünüze uygun yeni bir fırsat yakaladık.\n\n" .
                       "🏠 {$ilan->baslik}\n" .
                       "📍 {$context['listing']['location']}\n" .
                       "💰 Yıllık Tahmini Getiri: {$yield}\n" .
                       "📈 ROI: %{$roi}\n\n" .
                       "Detayları konuşmak ister misiniz?";
                       
            default:
                 return "Fırsat İlanı: {$ilan->baslik}. ROI: %{$roi}. Getiri: {$yield}.";
        }
    }

    /**
     * Prepare listing context with Cortex ROI data
     */
    private function prepareContext(Ilan $ilan, string $channel): array
    {
        // Load relationships if not loaded
        $ilan->loadMissing(['ilce', 'mahalle', 'ozellikler', 'danisman']);

        // Cortex Features
        $roiScore = $ilan->ozellikler->where('name', 'Sezonluk ROI')->first()?->pivot->deger ?? 'N/A';
        $yield = $ilan->ozellikler->where('name', 'Getiri')->first()?->pivot->deger ?? 'N/A';
        $benchmark = $ilan->ozellikler->where('name', 'Benchmark')->first()?->pivot->deger ?? 'N/A';

        return [
            'listing' => [
                'title' => $ilan->baslik,
                'price' => number_format($ilan->fiyat, 0, ',', '.') . ' ' . $ilan->para_birimi,
                'location' => ($ilan->ilce->ilce_adi ?? '') . ' / ' . ($ilan->mahalle->mahalle_adi ?? ''),
                'type' => $ilan->yayin_tipi_id, // Add logic to get human readable type if needed // context7-ignore
                'category' => $ilan->ana_kategori_id, // Add logic to get human readable category
                'size' => $ilan->net_m2 ?? $ilan->alan_m2 ?? 'N/A',
                'rooms' => $ilan->oda_sayisi ? $ilan->oda_sayisi . ' Oda' : '',
            ],
            'cortex_analysis' => [
                'roi_score' => $roiScore,
                'annual_yield' => $yield,
                'market_benchmark' => $benchmark,
                'is_hot_deal' => is_numeric(str_replace(['%', ' '], '', $roiScore)) && (float)str_replace(['%', ' '], '', $roiScore) > 80,
            ],
            'advisor' => [
                'name' => $ilan->danisman->name ?? 'Yalıhan AI',
                'contact' => $ilan->danisman->email ?? '',
            ],
            'channel' => $channel,
        ];
    }

    /**
     * Build channel-specific prompt
     */
    private function buildPrompt(string $channel, array $context): string
    {
        $jsonContext = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $basePrompt = "Act as an expert Real Estate Investment Consultant. Write a persuasive pitch for the following property. ";
        
        switch ($channel) {
            case 'telegram':
                $spec = "Format for TELEGRAM:\n- Use relevant emojis (🏠, 💰, 🚀, 📍).\n- Keep it concise (max 200 words).\n- Highlight the ROI and Yield immediately.\n- Use a 'Hook' headline.\n- Include a Call to Action (CTA) button text.";
                break;
            case 'whatsapp':
                $spec = "Format for WHATSAPP:\n- Professional yet personal tone.\n- Direct and clear.\n- Use minimal emojis (max 3-4).\n- Key facts first.\n- End with a question to provoke response.";
                break;
            case 'email':
                $spec = "Format for EMAIL:\n- Subject Line: Catchy and investment focused.\n- detailed analysis body.\n- Use bullet points for features.\n- Bold the Cortex Financial Analysis section.\n- Professional signature.";
                break;
            case 'sms':
                $spec = "Format for SMS:\n- Extremely short (max 160 chars).\n- Hook + Link.\n- No emojis (standard ASCII).";
                break;
            default:
                $spec = "Format: General marketing text.";
        }

        return "{$basePrompt}\n\n{$spec}\n\nContext:\n{$jsonContext}\n\nReturn ONLY the pitch text.";
    }
}

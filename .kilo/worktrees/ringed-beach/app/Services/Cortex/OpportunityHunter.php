<?php

namespace App\Services\Cortex;

use App\Models\Ilan;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Services\AIService;
use App\Services\TelegramService;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Log;

use App\Enums\IlanDurumu;

/**
 * Cortex Opportunity Hunter v1.0
 * 
 * 🏹 AVCI MODÜLÜ: %90+ skorlu fırsatları yakalar ve tebliğ eder.
 * 
 * @sealed 2025-12-31
 */
class OpportunityHunter
{
    protected MatchingEngine $matchingEngine;
    protected TelegramService $telegramService;
    protected AIService $aiService;
    protected CortexROIEngine $roiEngine;

    public function __construct(
        MatchingEngine $matchingEngine, 
        TelegramService $telegramService, 
        AIService $aiService,
        CortexROIEngine $roiEngine
    ) {
        $this->matchingEngine = $matchingEngine;
        $this->telegramService = $telegramService;
        $this->aiService = $aiService;
        $this->roiEngine = $roiEngine;
    }

    /**
     * Tüm sistemi tarar ve sıcak fırsatları bulur.
     */
    public function scanForOpportunities(): array
    {
        $opportunities = [];
        
        // Sadece Aktif ilanlar (Anayasal Uyum)
        $ilanlar = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->get();
        $leads = Lead::all();

        foreach ($leads as $lead) {
            foreach ($ilanlar as $ilan) {
                // ROI Hesapla
                $roiData = $this->roiEngine->calculateROI($ilan);

                // Matching Engine üzerinden skor hesapla
                $match = $this->matchingEngine->findMatchesForLead($lead, 100)
                    ->where('ilan.id', $ilan->id)
                    ->first();

                // Yatırımcı ise ROI skorunu Matching Engine skoruna dahil et (Ağırlıklı)
                if ($lead->yatirimci_profili && $match) {
                    $match['total_score'] = ($match['total_score'] * 0.7) + ($roiData['roi_score'] * 0.3);
                }

                if ($match && $match['total_score'] >= 90) {
                    // Fırsatı mühürle (Database)
                    $opportunityModel = Opportunity::updateOrCreate(
                        [
                            'ilan_id' => $ilan->id,
                            'lead_id' => $lead->id,
                        ],
                        [
                            'firsat_skoru' => $match['total_score'],
                            'skor_detayi' => array_merge($match['breakdown'], ['roi' => $roiData['roi_score']]),
                            'firsat_nedeni' => $this->generateReason($match, $roiData),
                            'ikna_metni' => $this->generatePersuasivePitch($ilan, $lead, $match, $roiData),
                            'firsat_durumu' => 'yeni',
                            'aktiflik_durumu' => true
                        ]
                    );

                    $opportunities[] = $opportunityModel;
                    
                    // Fırsatı tebliğ et
                    $this->notifyAdvisor($opportunityModel, $roiData);
                }
            }
        }

        return $opportunities;
    }

    /**
     * Danışmana Telegram üzerinden "Füze" hızıyla bildirim gönderir.
     */
    protected function notifyAdvisor(Opportunity $opportunity, array $roiData = []): void
    {
        $ilan = $opportunity->ilan;
        $lead = $opportunity->lead;
        $advisor = $ilan->danisman; // İlanın danışmanı

        if ($advisor && $advisor->telegram_id) {
            $roiText = "";
            if ($roiData && $roiData['roi_score'] > 0) {
                $roiText = "💰 *ROI Skoru:* %" . round($roiData['roi_score'], 1) . "\n" .
                           "⏳ *Amortisman:* {$roiData['payback_period_years']} Yıl\n" .
                           "📈 *Pazar:* {$roiData['market_comparison']}\n\n";
            }

            $message = "🚀 *CORTEX SICAK FIRSAT YAKALADI!* 🚀\n\n" .
                       "🎯 *Skor:* %" . round($opportunity->firsat_skoru, 1) . "\n" .
                       "🏠 *İlan:* {$ilan->baslik}\n" .
                       "👤 *Müşteri:* {$lead->ad_soyad}\n" .
                       "📍 *Bölge:* {$ilan->ilce_id} / {$ilan->mahalle_id}\n\n" .
                       $roiText .
                       "💡 *Neden:* {$opportunity->firsat_nedeni}\n\n" .
                       "📣 *AI İkna Metni:* \n_{$opportunity->ikna_metni}_\n\n" .
                       "⚡ _Hemen iletişime geçin!_";

            $this->telegramService->sendMessage($advisor->telegram_id, $message);
            
            LogService::info("OpportunityHunter: Fırsat tebliğ edildi.", [
                'advisor_id' => $advisor->id,
                'score' => $opportunity->firsat_skoru
            ]);
        }
    }

    /**
     * Fırsatın nedenini açıklar.
     */
    protected function generateReason(array $match, array $roiData = []): string
    {
        $breakdown = $match['breakdown'];
        $reasons = [];

        if ($breakdown['location'] >= 90) $reasons[] = "Mükemmel lokasyon uyumu";
        if ($breakdown['budget'] >= 90) $reasons[] = "Tam bütçe eşleşmesi";
        if ($breakdown['vision'] >= 80) $reasons[] = "Yüksek görsel kalite";
        if ($breakdown['features'] >= 90) $reasons[] = "Talep edilen tüm özellikler mevcut";
        
        if (isset($roiData['is_high_yield']) && $roiData['is_high_yield']) {
            $reasons[] = "Yüksek yatırım getirisi (ROI)";
        }

        return implode(", ", $reasons) ?: "Genel yüksek uyum skoru.";
    }

    /**
     * AI kullanarak ikna edici bir satış metni üretir.
     */
    protected function generatePersuasivePitch(Ilan $ilan, Lead $lead, array $match, array $roiData = []): string
    {
        $roiContext = "";
        if ($roiData && $roiData['roi_score'] > 0) {
            $roiContext = "Yatırım Analizi: Bu mülk {$roiData['payback_period_years']} yılda kendini amorti ediyor. " .
                          "Bölge kira ortalaması aylık {$roiData['monthly_rental_income']} TL. " .
                          "Pazar Durumu: {$roiData['market_comparison']}\n";
        }

        $prompt = "Sen bir profesyonel gayrimenkul danışmanı asistanısın. " .
                  "Aşağıdaki ilan ve müşteri (lead) eşleşmesi için danışmanın müşteriye WhatsApp üzerinden gönderebileceği, " .
                  "ikna edici, kısa ve profesyonel bir satış metni hazırla.\n\n" .
                  "Müşteri: {$lead->ad_soyad}\n" .
                  "İlan: {$ilan->baslik}\n" .
                  "Eşleşme Skoru: %" . round($match['total_score'], 1) . "\n" .
                  "Öne Çıkan Özellikler: " . $this->generateReason($match, $roiData) . "\n" .
                  $roiContext .
                  "\nMetin samimi ama profesyonel olsun. Rasyonel verileri (ROI, amortisman) duygusal faydalarla birleştir. Emojiler kullanabilirsin.";

        try {
            $response = $this->aiService->generate($prompt);
            return is_string($response) ? $response : "Bu mülk tam size göre! Detaylar için iletişime geçelim.";
        } catch (\Exception $e) {
            Log::error("OpportunityHunter: AI Pitch hatası: " . $e->getMessage());
            return "Mükemmel bir fırsat yakaladık, detayları konuşmak isterim.";
        }
    }}

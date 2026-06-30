<?php

namespace App\Listeners;

use App\Events\IlanCreated;
use App\Jobs\NotifyN8nAboutNewIlan;
use App\Jobs\HandleUrgentMatch;
use App\Notifications\NewMatchingListingFound;
use App\Services\AI\SmartPropertyMatcherAI;
use App\Services\Logging\LogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Find Matching Demands Listener
 *
 * Context7: Tersine Eşleştirme (Reverse Matching) Listener
 *
 * Yeni ilan eklendiğinde, bu ilana uygun talepleri bulur ve
 * danışmanlara bildirim gönderir.
 */
class FindMatchingDemands implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * SmartPropertyMatcherAI servisi
     */
    protected SmartPropertyMatcherAI $matcher;

    /**
     * Create the event listener.
     */
    public function __construct(SmartPropertyMatcherAI $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * Handle the event.
     */
    public function handle(IlanCreated $event): void
    {
        $ilan = $event->ilan;
        $startTime = microtime(true);

        try {
            LogService::ai(
                'reverse_matching_started',
                'FindMatchingDemands',
                [
                    'ilan_id' => $ilan->id,
                    'ilan_baslik' => $ilan->baslik,
                ]
            );

            // Tersine eşleştirme: İlan için uygun talepleri bul
            $matches = $this->matcher->reverseMatch($ilan);

            $matchedCount = count($matches);
            $notificationCount = 0;

            // Her eşleşme için danışmana bildirim gönder
            foreach ($matches as $match) {
                $talep = $match['talep'];
                $score = $match['score'];

                try {
                    // Urgency level hesapla
                    $urgencyLevel = $this->calculateUrgencyLevel($ilan, $talep, $score);

                    // Kritik fırsat kontrolü: Score > 90 ise HandleUrgentMatch listener'ını tetikle
                    if ($score > 90) {
                        $urgentMatchData = [
                            'score' => $score,
                            'urgency_level' => $urgencyLevel,
                            'type' => 'ilan_match',
                            'ilan_id' => $ilan->id,
                            'ilan_baslik' => $ilan->baslik,
                            'talep_id' => $talep->id,
                            'talep_baslik' => $talep->baslik,
                        ];

                        // HandleUrgentMatch listener'ını dispatch et (Queue'da)
                        HandleUrgentMatch::dispatch($urgentMatchData);
                    }

                    // Danışmana bildirim gönder
                    $danisman = $talep->danisman;

                    if ($danisman) {
                        $danisman->notify(new NewMatchingListingFound(
                            $ilan,
                            $talep,
                            $score
                        ));
                        $notificationCount++;

                        LogService::ai(
                            'reverse_match_notification_sent',
                            'FindMatchingDemands',
                            [
                                'ilan_id' => $ilan->id,
                                'talep_id' => $talep->id,
                                'score' => $score,
                                'urgency_level' => $urgencyLevel,
                                'danisman_id' => $danisman->id,
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    LogService::error(
                        'Reverse matching notification error',
                        [
                            'talep_id' => $talep->id,
                            'ilan_id' => $ilan->id,
                            'danisman_id' => $danisman?->id,
                            'error' => $e->getMessage(),
                        ],
                        $e,
                        LogService::CHANNEL_AI
                    );
                }
            }

            $duration = microtime(true) - $startTime;

            LogService::ai(
                'reverse_matching_completed',
                'FindMatchingDemands',
                [
                    'ilan_id' => $ilan->id,
                    'matched_count' => $matchedCount,
                    'notification_count' => $notificationCount,
                    'duration_ms' => round($duration * 1000, 2),
                ]
            );

            // ✅ n8n'e bildirim gönder (Queue'da)
            NotifyN8nAboutNewIlan::dispatch($ilan->id);
        } catch (\Exception $e) {
            LogService::error(
                'Reverse matching failed',
                [
                    'ilan_id' => $ilan->id,
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            // Job'u tekrar denemek için throw et
            throw $e;
        }
    }

    /**
     * Urgency level hesapla
     *
     * Context7: Müşteri risk analizi ve danışman yükü analizine göre urgency level belirle
     *
     * @param \App\Models\Ilan $ilan
     * @param \App\Models\Talep $talep
     * @param float $score
     * @return string
     */
    protected function calculateUrgencyLevel($ilan, $talep, float $score): string
    {
        $urgencyFactors = [];

        // 1. Skor faktörü
        if ($score >= 95) {
            $urgencyFactors[] = 'high_score';
        }

        // 2. Müşteri risk analizi (son iletişim tarihine göre)
        $sonIletisim = $talep->updated_at ?? $talep->created_at;
        $gunFarki = now()->diffInDays($sonIletisim);

        if ($gunFarki > 20) {
            $urgencyFactors[] = 'cold_customer'; // Soğumuş müşteri
        } elseif ($gunFarki > 10) {
            $urgencyFactors[] = 'warm_customer'; // Ilık müşteri
        }

        // 3. Danışman yükü analizi (aktif talep sayısına göre)
        $danisman = $talep->danisman;
        if ($danisman) {
            // Aktif talep sayısı (bekleyen veya devam eden)
            $aktifTalepSayisi = $danisman->talepler()
                ->whereIn('talep_durumu', ['aktif', 'bekliyor', 'devam_ediyor']) // Context7: Direct column reference
                ->count() ?? 0;

            if ($aktifTalepSayisi > 15) {
                $urgencyFactors[] = 'overloaded_consultant'; // Aşırı yüklü danışman
            }
        }

        // 4. Fiyat uyumu (eğer varsa - match verisi yoksa atla)
        // Not: Fiyat uyumu bilgisi SmartPropertyMatcherAI'den gelirse burada kullanılabilir

        // Urgency level belirleme
        if (in_array('cold_customer', $urgencyFactors) && in_array('high_score', $urgencyFactors)) {
            return 'CRITICAL'; // Soğumuş müşteri + Yüksek skor = KRİTİK
        } elseif (in_array('overloaded_consultant', $urgencyFactors) && $score >= 90) {
            return 'CRITICAL'; // Aşırı yüklü danışman + Yüksek skor = KRİTİK
        } elseif ($score >= 95) {
            return 'CRITICAL'; // Mükemmel skor = KRİTİK
        } elseif ($score >= 90) {
            return 'HIGH'; // Yüksek skor = YÜKSEK
        } else {
            return 'NORMAL'; // Normal
        }
    }
}

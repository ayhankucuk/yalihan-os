<?php

namespace App\Jobs\Cortex;

use App\Models\Ilan;
use App\Services\AI\VisionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AnalyzeListingPhotosJob
 *
 * 👁️ Cortex Vision: İlanın en iyi 5 fotoğrafını analiz eder.
 * Sonuçları ilan tablosuna mühürler.
 */
class AnalyzeListingPhotosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Ilan $ilan;

    /**
     * Create a new job instance.
     */
    public function __construct(Ilan $ilan)
    {
        $this->ilan = $ilan;
    }

    /**
     * Execute the job.
     */
    public function handle(VisionService $visionService): void
    {
        try {
            // Sadece ilk 5 fotoğrafı al (maliyet kontrolü)
            $fotograflar = $this->ilan->fotograflar()
                ->orderBy('display_order', 'asc')
                ->take(5)
                ->get();

            if ($fotograflar->isEmpty()) {
                return;
            }

            $aggregatedResults = [
                'vision_analyzed_at' => now()->toDateTimeString(),
                'photos_analyzed' => [],
                'all_features' => [],
                'room_types' => [],
                'is_verified' => true, // Default true, will be false if any photo fails
                'verification_details' => []
            ];

            $totalQualityScore = 0;
            $photoCount = 0;

            foreach ($fotograflar as $foto) {
                $result = $visionService->analizEt($foto->dosya_yolu, [
                    'baslik' => $this->ilan->baslik,
                    'aciklama' => $this->ilan->aciklama,
                    'kategori' => $this->ilan->ana_kategori_id
                ]);

                $aggregatedResults['photos_analyzed'][] = [
                    'id' => $foto->id,
                    'result' => $result
                ];

                if (isset($result['is_verified']) && $result['is_verified'] === false) {
                    $aggregatedResults['is_verified'] = false;
                    $aggregatedResults['verification_details'][] = "Photo ID {$foto->id} is not verified.";
                }

                $aggregatedResults['all_features'] = array_unique(array_merge(
                    $aggregatedResults['all_features'],
                    $result['detected_features'] ?? []
                ));

                $aggregatedResults['room_types'][] = $result['room_type'] ?? 'Bilinmiyor';

                $totalQualityScore += $result['quality_score'] ?? 0;
                $photoCount++;
            }

            // Ortalama kalite puanı
            $avgQualityScore = $photoCount > 0 ? round($totalQualityScore / $photoCount, 1) : 0;

            // İlanı güncelle
            $this->ilan->update([
                'quality_score' => $avgQualityScore,
                'ai_metadata' => array_merge(
                    is_array($this->ilan->ai_metadata) ? $this->ilan->ai_metadata : [],
                    ['vision' => $aggregatedResults]
                )
            ]);

            // Context7: Vision Analizi Tamamlandı Logu
            \App\Services\Logging\LogService::info("Cortex Vision: #{$this->ilan->id} nolu ilan için Vision Analizi Tamamlandı.", [
                'ilan_id' => $this->ilan->id,
                'quality_score' => $avgQualityScore,
                'is_verified' => $aggregatedResults['is_verified']
            ]);

            Log::info("Cortex Vision: #{$this->ilan->id} nolu ilan fotoğrafları analiz edildi. Skor: {$avgQualityScore}");

        } catch (\Exception $e) {
            Log::error("Cortex Vision Job Hatası (#{$this->ilan->id}): " . $e->getMessage());
        }
    }
}

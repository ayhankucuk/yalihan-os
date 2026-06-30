<?php

namespace App\Services\AI;

use App\Models\Ilan;
use App\Models\IlanFotografi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CortexVisionService
{
    /**
     * İlan fotoğraflarını akıllıca sıralar
     *
     * Kriterler:
     * 1. Çözünürlük (Yüksek > Düşük)
     * 2. Oran (Yatay > Dikey) - Kapak için
     * 3. Sahne (Dış > Salon > Mutfak > Yatak Odası > Banyo > Detay) - Vision API entegre edilene kadar dosya boyutu/oran kullanacağız.
     */
    public function smartRankPhotos(Ilan $ilan): array
    {
        // Support both relationship names if needed, prioritizing 'fotograflar'
        $photos = $ilan->fotograflar()->get();

        if ($photos->isEmpty()) {
            // Fallback for new system if applicable
            if (method_exists($ilan, 'photos')) {
                $photos = $ilan->photos()->get();
            }

            if ($photos->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Fotoğraf bulunamadı'
                ];
            }
        }

        $rankedPhotos = $photos->map(function ($photo) {
            $score = 0;
            $meta = $this->analyzePhotoMetadata($photo);

            // 1. Çözünürlük Puanı (Max 40)
            if ($meta['width'] >= 1920) $score += 40;
            elseif ($meta['width'] >= 1280) $score += 30;
            elseif ($meta['width'] >= 800) $score += 15;

            // 2. Oran Puanı (Max 30) - Kapak için Yatay (Landscape) tercih edilir
            $ratio = $meta['ratio'];
            if ($ratio >= 1.3 && $ratio <= 1.8) $score += 30; // 4:3 or 16:9 ideal
            elseif ($ratio > 1.0) $score += 20; // Landscape
            elseif ($ratio == 1.0) $score += 10; // Square

            // 3. Dosya Boyutu (Kalite sinyali) (Max 10)
            if ($meta['size_kb'] > 200) $score += 10;

            // 4. Vision Etiketi (Mock - Gelecekte API ile)
            // Şimdilik dosya isminde "kapak", "main", "salon" varsa puan ver
            // Modelde 'dosya_adi' veya 'file_name' olabilir
            $filename = strtolower($photo->dosya_adi ?? $photo->file_name ?? '');

            if (str_contains($filename, 'kapak') || str_contains($filename, 'main')) $score += 20;
            if (str_contains($filename, 'salon')) $score += 15;
            if (str_contains($filename, 'mutfak')) $score += 10;
            if (str_contains($filename, 'banyo')) $score -= 5; // Banyo kapak olmasın

            return [
                'id' => $photo->id,
                'score' => $score,
                'meta' => $meta,
                'photo' => $photo
            ];
        });

        // Puanına göre sırala
        $sorted = $rankedPhotos->sortByDesc('score')->values();

        // Update display sequence
        $sequence = 1;
        foreach ($sorted as $item) {
            // Model 'display_order' veya 'sira' kullanıyor olabilir
            $photo = $item['photo'];

            // Model method check before update to avoid crashes
            if (in_array('display_order', $photo->getFillable())) {
                 $photo->update(['display_order' => $sequence++]);
            } elseif (in_array('sira', $photo->getFillable())) {
                 $photo->update(['sira' => $sequence++]);
            }
        }

        return [
            'success' => true,
            'processed_count' => $photos->count(),
            'top_pick' => $sorted->first()['photo']->id ?? null,
            'top_score' => $sorted->first()['score'] ?? 0
        ];
    }

    private function analyzePhotoMetadata($photo): array
    {
        // Dosya yolu - Model alanlarına göre esnek
        $path = $photo->dosya_yolu ?? $photo->path ?? $photo->url ?? null;

        // Varsayılan
        $width = 0;
        $height = 0;
        $size = 0;
        $ratio = 0;

        if ($path) {
            try {
                // Eğer full path public diskte ise
                $disk = Storage::disk('public');
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */

                if ($disk->exists($path)) {
                    $size = $disk->size($path) / 1024; // KB

                    $fullPath = $disk->path($path);
                    if (file_exists($fullPath)) {
                        $dims = @getimagesize($fullPath);
                        if ($dims) {
                            $width = $dims[0];
                            $height = $dims[1];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Sessizce devam et, logla
                // Log::warning("CortexVision metadata error for photo {$photo->id}: " . $e->getMessage());
            }
        }

        if ($height > 0) {
            $ratio = $width / $height;
        }

        return [
            'width' => $width,
            'height' => $height,
            'ratio' => $ratio,
            'size_kb' => $size
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Ilan;
use App\Models\IlanFotografi;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Context7: Ilan foto analiz ve dizilim servisi
 * - analyzeImage: oda tipi + dummy kalite puani üretir
 * - getOptimizedSequence: dizilim + ana fotoğraf önerisi
 */
class IlanVisionService
{
    /**
     * Fotoğrafı analiz et: oda tipi ve 0-100 arası puani üret.
     */
    public function analyzeImage(IlanFotografi $foto): Collection
    {
        $odaTipi = $this->tahminEtOdaTipi($foto);
        $puani = $this->hesaplaPuani($odaTipi);

        return collect([
            'foto_id' => $foto->id,
            'oda_tipi' => $odaTipi,
            'puani' => $puani,
            'is_suggested_main' => false,
            'badge_message' => null,
            'cta_message' => null,
        ]);
    }

    /**
     * İlanın fotoğraflarını analiz eder, ideal sıralamayı döndürür.
     */
    public function getOptimizedSequence(Ilan $ilan): Collection
    {
        $fotolar = $this->resolveFotos($ilan);
        if ($fotolar->isEmpty()) {
            return collect();
        }

        $analyzed = $fotolar->map(fn (IlanFotografi $foto) => $this->analyzeImage($foto))
            ->sortByDesc('puani')
            ->values();

        $diversified = $this->applyDiversity($analyzed);
        $withSuggestion = $this->flagSuggestedMain($ilan, $diversified);

        // Sıra numaralarını ata
        return $withSuggestion->values()->each(function (&$item, $idx) {
            $item['sira'] = $idx + 1;
        });
    }

    private function resolveFotos(Ilan $ilan): Collection
    {
        if (method_exists($ilan, 'fotograflar') && $ilan->fotograflar instanceof Collection) {
            return $ilan->fotograflar;
        }
        if (property_exists($ilan, 'fotograflar') && $ilan->fotograflar instanceof Collection) {
            return $ilan->fotograflar;
        }
        if (method_exists($ilan, 'fotos') && $ilan->fotos instanceof Collection) {
            return $ilan->fotos;
        }
        if (property_exists($ilan, 'fotos') && $ilan->fotos instanceof Collection) {
            return $ilan->fotos;
        }

        return collect();
    }

    private function tahminEtOdaTipi(IlanFotografi $foto): string
    {
        $adi = Str::lower($foto->dosya_adi ?? '');
        $etiketler = collect($foto->etiketler ?? [])->map(fn ($e) => Str::lower($e))->implode(' ');
        $metin = $adi.' '.$etiketler;

        if (Str::contains($metin, ['salon', 'living', 'livingroom'])) {
            return 'salon';
        }
        if (Str::contains($metin, ['cephe', 'dis', 'dış', 'facade', 'front'])) {
            return 'cephe';
        }
        if (Str::contains($metin, ['mutfak', 'kitchen'])) {
            return 'mutfak';
        }
        if (Str::contains($metin, ['banyo', 'bath'])) {
            return 'banyo';
        }
        if (Str::contains($metin, ['wc', 'tuvalet', 'lavabo'])) {
            return 'wc';
        }

        return 'diger';
    }

    private function hesaplaPuani(string $odaTipi): int
    {
        return match ($odaTipi) {
            'salon', 'cephe' => random_int(80, 100),
            'banyo', 'wc' => random_int(10, 30),
            default => random_int(40, 70),
        };
    }

    private function applyDiversity(Collection $analyzed): Collection
    {
        $priority = ['salon', 'cephe', 'mutfak', 'diger', 'banyo', 'wc'];

        // Öncelikle banyo/wc sona, salon/cephe başa gelecek şekilde grupla
        $grouped = collect($priority)->mapWithKeys(fn ($tip) => [
            $tip => $analyzed->where('oda_tipi', $tip)->values(),
        ]);

        // Round-robin çeşitlilik: aynı tip ardışık gelmesin
        $result = collect();
        $lastType = null;
        $iterationGuard = $analyzed->count() * 2;

        while ($result->count() < $analyzed->count() && $iterationGuard-- > 0) {
            foreach ($priority as $tip) {
                $bucket = $grouped[$tip];
                if ($bucket->isEmpty()) {
                    continue;
                }
                if ($lastType === $tip && $bucket->count() > 1) {
                    continue; // çeşitlilik için sonraki tipe bak
                }
                $item = $bucket->shift();
                $grouped[$tip] = $bucket;
                $result->push($item);
                $lastType = $tip;
                if ($result->count() === $analyzed->count()) {
                    break 2;
                }
            }
        }

        // Artan kalırsa ekle
        foreach ($priority as $tip) {
            $bucket = $grouped[$tip];
            while ($bucket->isNotEmpty()) {
                $result->push($bucket->shift());
            }
        }

        return $result->values();
    }

    private function flagSuggestedMain(Ilan $ilan, Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $currentMainId = $ilan->ana_fotograf_id ?? null;
        $currentMain = $currentMainId ? $items->firstWhere('foto_id', $currentMainId) : null;
        $currentLowQuality = $currentMain && ($currentMain['puani'] < 60 || in_array($currentMain['oda_tipi'], ['banyo', 'wc'], true));

        // En iyi salon/cephe adayı
        $bestCandidate = $items
            ->filter(fn ($i) => in_array($i['oda_tipi'], ['salon', 'cephe'], true))
            ->sortByDesc('puani')
            ->first();

        if ($currentLowQuality && $bestCandidate) {
            return $items->map(function ($item) use ($bestCandidate) {
                if ($item['foto_id'] === $bestCandidate['foto_id']) {
                    $item['is_suggested_main'] = true;
                    $item['badge_message'] = 'AI Tavsiyesi';
                    $item['cta_message'] = 'Bu salon fotoğrafını ana görsel yapmak ister misiniz?';
                }
                return $item;
            });
        }

        return $items;
    }
}

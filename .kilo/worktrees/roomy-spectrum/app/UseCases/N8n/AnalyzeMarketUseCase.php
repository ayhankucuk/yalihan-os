<?php

namespace App\UseCases\N8n;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\UseCases\N8n\DTOs\AnalyzeMarketDTO;

class AnalyzeMarketUseCase
{
    public function handle(AnalyzeMarketDTO $dto): array
    {
        // %20 esneme payı ile m2 aralığı
        $m2Min = $dto->m2 * 0.8;
        $m2Max = $dto->m2 * 1.2;
        $location = $dto->location;
        $type = $dto->tip;

        $emsalIlans = Ilan::query()
            ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($location) {
                $query->where('ilce_id', 'like', "%{$location}%")
                    ->orWhere('mahalle_id', 'like', "%{$location}%")
                    ->orWhere('tam_adres', 'like', "%{$location}%");
            })
            ->whereBetween('metrekare', [$m2Min, $m2Max])
            ->whereHas('altKategori', function ($query) use ($type) {
                $query->where('slug', 'like', "%{$type}%");
            })
            ->with(['il:id,il_adi', 'ilce:id,ilce_adi', 'mahalle:id,mahalle_adi'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($ilan) {
                return [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'fiyat' => $ilan->fiyat,
                    'para_birimi' => $ilan->para_birimi,
                    'metrekare' => $ilan->metrekare,
                    'location' => [
                        'il' => $ilan->il->il_adi ?? null,
                        'ilce' => $ilan->ilce->ilce_adi ?? null,
                        'mahalle' => $ilan->mahalle->mahalle_adi ?? null,
                    ],
                    'tarih' => $ilan->created_at->format('Y-m-d'),
                    'fiyat_per_m2' => $ilan->metrekare > 0 ? round($ilan->fiyat / $ilan->metrekare, 2) : null,
                ];
            });

        return [
            'emsal_count' => $emsalIlans->count(),
            'emsal_ilans' => $emsalIlans,
            'search_criteria' => [
                'location' => $location,
                'm2' => $dto->m2,
                'm2_range' => [$m2Min, $m2Max],
                'tip' => $type,
            ],
        ];
    }
}

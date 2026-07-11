<?php

declare(strict_types=1);

namespace App\Jobs\Location;

use App\Models\Ilan;
use App\Models\User;
use App\Queue\Contracts\TenantAwareJobInterface;
use App\Queue\Middleware\RestoreTenantContext;
use App\Services\SaaS\TenantContextService;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CalculateTransitDurationJob
 *
 * Sprint 6.2: Calculates transit times using Google Distance Matrix API (with mock fallback).
 */
class CalculateTransitDurationJob implements ShouldQueue, TenantAwareJobInterface
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    // Milas-Bodrum Airport (BJV) Default Coordinates
    private const BJV_LAT = 37.2506;
    private const BJV_LNG = 27.6689;

    public function __construct(
        public int $ilanId,
        public int $userId
    ) {
        $this->onQueue('high');
    }

    public function getTenantId(): ?int
    {
        $ilan = Ilan::withoutGlobalScopes()->find($this->ilanId);
        return $ilan?->tenant_id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function middleware(): array
    {
        return [new RestoreTenantContext(app(TenantContextService::class))];
    }

    public function handle(): void
    {
        $ilan = Ilan::withoutGlobalScopes()->find($this->ilanId);
        if (!$ilan) {
            Log::warning('CalculateTransitDurationJob: Ilan bulunamadı', ['ilan_id' => $this->ilanId]);
            return;
        }

        $lat = $ilan->lat;
        $lng = $ilan->lng;

        if (empty($lat) || empty($lng)) {
            Log::warning('CalculateTransitDurationJob: Ilan coordinates are missing. Cannot calculate transit.', ['ilan_id' => $this->ilanId]);
            return;
        }

        $apiKey = config('location.google_maps.api_key') ?? config('services.google_maps.api_key');

        try {
            if (empty($apiKey)) {
                $transitData = $this->calculateMockTransit((float) $lat, (float) $lng);
            } else {
                $response = Http::timeout(5)
                    ->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                        'origins'      => sprintf('%f,%f', $lat, $lng),
                        'destinations' => sprintf('%f,%f', self::BJV_LAT, self::BJV_LNG),
                        'key'          => $apiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $element = $data['rows'][0]['elements'][0] ?? null;

                    if ($element && ($element['status'] ?? '') === 'OK') { // context7-ignore
                        $distanceKm = round($element['distance']['value'] / 1000, 1);
                        $durationMin = (int) round($element['duration']['value'] / 60);

                        $transitData = [
                            'airport_bjv' => [
                                'distance_km'      => $distanceKm,
                                'duration_minutes' => $durationMin,
                            ],
                        ];
                    } else {
                        $transitData = $this->calculateMockTransit((float) $lat, (float) $lng);
                    }
                } else {
                    $transitData = $this->calculateMockTransit((float) $lat, (float) $lng);
                }
            }

            // Update metadata safely
            $currentMeta = $ilan->metadata ?? [];
            $locIntel = $currentMeta['location_intelligence'] ?? [];
            $locIntel['transit'] = $transitData;

            $ilanCrudService = app(IlanCrudService::class);
            $ilanCrudService->update($ilan, [
                'baslik'          => $ilan->baslik,
                'aciklama'        => $ilan->aciklama,
                'danisman_id'     => $ilan->danisman_id,
                'ilan_sahibi_id'  => $ilan->ilan_sahibi_id,
                'fiyat'           => $ilan->fiyat,
                'para_birimi'     => $ilan->para_birimi,
                'ana_kategori_id' => $ilan->ana_kategori_id,
                'alt_kategori_id' => $ilan->alt_kategori_id,
                'yayin_tipi_id'   => $ilan->yayin_tipi_id,
                'il'              => $ilan->il,
                'ilce'            => $ilan->ilce,
                'mahalle'         => $ilan->mahalle,
                'il_id'           => $ilan->il_id,
                'ilce_id'         => $ilan->ilce_id,
                'mahalle_id'      => $ilan->mahalle_id,
                'lat'             => $ilan->lat,
                'lng'             => $ilan->lng,
                'adres'           => $ilan->adres,
                'metadata'        => [
                    'location_intelligence' => $locIntel,
                ],
            ]);

            Log::info('CalculateTransitDurationJob completed successfully', [
                'ilan_id' => $this->ilanId,
                'transit' => $transitData,
            ]);

        } catch (\Throwable $e) {
            Log::error('CalculateTransitDurationJob failed', [
                'ilan_id' => $this->ilanId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function calculateMockTransit(float $lat, float $lng): array
    {
        // Simple Haversine calculation to Milas-Bodrum Airport (BJV)
        $earthRadius = 6371; // in km
        $dLat = deg2rad(self::BJV_LAT - $lat);
        $dLng = deg2rad(self::BJV_LNG - $lng);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat)) * cos(deg2rad(self::BJV_LAT)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distanceKm = round($earthRadius * $c, 1);

        // Approximate duration: 1.3 minutes per km ( Bodum roads average )
        $durationMin = (int) max(10, round($distanceKm * 1.3));

        return [
            'airport_bjv' => [
                'distance_km'      => $distanceKm,
                'duration_minutes' => $durationMin,
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs\Location;

use App\Models\Ilan;
use App\Models\User;
use App\Queue\Contracts\TenantAwareJobInterface;
use App\Queue\Middleware\RestoreTenantContext;
use App\Services\SaaS\TenantContextService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TKGMGeocodeJob
 *
 * Sprint 6.2: Resolves address to coordinates asynchronously.
 * Implements fallback geodata on external API failure.
 */
class TKGMGeocodeJob implements ShouldQueue, TenantAwareJobInterface
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

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
            Log::warning('TKGMGeocodeJob: Ilan bulunamadı', ['ilan_id' => $this->ilanId]);
            return;
        }

        try {
            $ilName = $ilan->il?->il_adi !== 'Belirtilmemiş' ? $ilan->il?->il_adi : null;
            $ilceName = $ilan->ilce?->ilce_adi !== 'Belirtilmemiş' ? $ilan->ilce?->ilce_adi : null;
            $mahalleName = $ilan->mahalle?->mahalle_adi !== 'Belirtilmemiş' ? $ilan->mahalle?->mahalle_adi : null;

            // Build geocoding query string
            $queryParts = array_filter([$ilan->adres, $mahalleName, $ilceName, $ilName, 'Türkiye']);
            $query = implode(', ', $queryParts);

            Log::info('TKGMGeocodeJob: Geocoding query prepared', [
                'ilan_id' => $this->ilanId,
                'query'   => $query,
            ]);

            $response = Http::timeout(10)
                ->retry(2, 500)
                ->withHeaders([
                    'User-Agent' => 'YalihanEmlak/1.0',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format'          => 'json',
                    'q'               => $query,
                    'countrycodes'    => 'tr',
                    'limit'           => 1,
                    'accept-language' => 'tr',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                    $lat = (float) $data[0]['lat'];
                    $lng = (float) $data[0]['lon'];

                    $this->updateCoordinates($ilan, $lat, $lng);
                    return;
                }
            }

            Log::warning('TKGMGeocodeJob: External geocoding failed or returned empty. Falling back to database defaults.', [
                'ilan_id' => $this->ilanId,
            ]);

            $this->applyFallbackCoordinates($ilan);

        } catch (\Throwable $e) {
            Log::error('TKGMGeocodeJob failed', [
                'ilan_id' => $this->ilanId,
                'error'   => $e->getMessage(),
            ]);

            $this->applyFallbackCoordinates($ilan);
        }
    }

    private function updateCoordinates(Ilan $ilan, float $lat, float $lng): void
    {
        $ilanCrudService = app(\App\Services\Ilan\IlanCrudService::class);
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
            'lat'             => $lat,
            'lng'             => $lng,
            'adres'           => $ilan->adres,
            'metadata'        => $ilan->metadata ?? [],
        ]);

        Log::info('TKGMGeocodeJob: Coordinates updated successfully', [
            'ilan_id' => $this->ilanId,
            'lat'     => $lat,
            'lng'     => $lng,
        ]);
    }

    private function applyFallbackCoordinates(Ilan $ilan): void
    {
        $lat = null;
        $lng = null;

        // Try mahalle default first
        if ($ilan->mahalle_id) {
            $mahalle = $ilan->mahalle()->first();
            if ($mahalle) {
                $lat = $mahalle->lat;
                $lng = $mahalle->lng;
            }
        }

        // Try ilce default second
        if (($lat === null || $lng === null) && $ilan->ilce_id) {
            $ilce = $ilan->ilce()->first();
            if ($ilce) {
                $lat = $ilce->lat;
                $lng = $ilce->lng;
            }
        }

        // Try il default third
        if (($lat === null || $lng === null) && $ilan->il_id) {
            $il = $ilan->il()->first();
            if ($il) {
                $lat = $il->lat;
                $lng = $il->lng;
            }
        }

        // Ultimate Mugla default fallback
        if ($lat === null || $lng === null) {
            $lat = 37.2153;
            $lng = 28.3636;
        }

        $this->updateCoordinates($ilan, (float) $lat, (float) $lng);
    }
}

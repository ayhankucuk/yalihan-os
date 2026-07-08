<?php

namespace App\Services\Location;

use App\DTOs\Location\GeocodingResultDTO;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding Service — Sprint 6.2
 *
 * Türkçe adresi koordinata çevirir.
 *
 * Pipeline:
 *   1. Cache kontrol (30 gün TTL)
 *   2. Nominatim (OSM) — ücretsiz, 1 req/s limit
 *   3. Fallback: TürkiyeAdresDB (il/ilçe/mahalle → orta nokta)
 *
 * SAB Authority: Bu servis sadece koordinat döndürür, Ilan modeline yazmaz.
 */
class GeocodingService
{
    private const CACHE_TTL_DAYS = 30;
    private const NOMINATIM_BASE = 'https://nominatim.openstreetmap.org/search';
    private const USER_AGENT = 'YalihanEmlak/1.0 (bodrum property platform)';
    private const NOMINATIM_TIMEOUT = 5;

    /**
     * Adresi koordinata çevir.
     *
     * @param  string      $address  Türkçe adres string
     * @param  bool        $forceRefresh  Cache'i atla
     * @return GeocodingResultDTO
     */
    public function resolve(string $address, bool $forceRefresh = false): GeocodingResultDTO
    {
        if (empty(trim($address))) {
            return GeocodingResultDTO::failure('Boş adres');
        }

        $normalized = $this->normalize($address);
        $cacheKey = "geocode:v1:{$normalized}";

        if (!$forceRefresh) {
            $cached = $this->getFromCache($cacheKey);
            if ($cached !== null) {
                return $cached->withCache(true);
            }
        }

        // Step 1: Nominatim
        $result = $this->resolveNominatim($normalized);
        if ($result->success) {
            $this->putToCache($cacheKey, $result);
            return $result;
        }

        // Step 2: Fallback — Türkiye AdresDB (structured address)
        $fallback = $this->resolveFromAdresDB($normalized);
        if ($fallback->success) {
            $this->putToCache($cacheKey, $fallback);
            return $fallback;
        }

        Log::warning('GeocodingService: All methods failed', [
            'address' => $address,
            'normalized' => $normalized,
        ]);

        return $result; // Nominatim hatasını döndür
    }

    /**
     * Ilan'ın il/ilçe/mahalle bilgisinden koordinat al (fallback).
     *
     * @param  int  $ilId
     * @param  int  $ilceId
     * @param  int  $mahalleId
     * @return GeocodingResultDTO
     */
    public function resolveFromIds(int $ilId, int $ilceId, int $mahalleId): GeocodingResultDTO
    {
        $mahalle = Mahalle::select(['id', 'mahalle_adi', 'lat', 'lng', 'ilce_id'])
            ->with(['ilce:id,ilce_adi,il_id', 'ilce.il:id,il_adi'])
            ->find($mahalleId);

        if (!$mahalle) {
            return GeocodingResultDTO::failure('Mahalle bulunamadı');
        }

        // Mahalle koordinatı varsa kullan
        if ($mahalle->lat && $mahalle->lng && $mahalle->lat != 0 && $mahalle->lng != 0) {
            return new GeocodingResultDTO(
                success: true,
                lat: (float) $mahalle->lat,
                lng: (float) $mahalle->lng,
                source: 'adres_db',
                displayName: $this->buildDisplayName($mahalle),
                rawData: null,
                error: null,
            );
        }

        // Ilçe koordinatı — mahalleler tablosunda ilçe koordinatı varsa
        $ilce = $mahalle->ilce;
        if ($ilce && $ilce->lat && $ilce->lng) {
            return new GeocodingResultDTO(
                success: true,
                lat: (float) $ilce->lat,
                lng: (float) $ilce->lng,
                source: 'adres_db',
                displayName: $this->buildDisplayName($mahalle),
                rawData: null,
                error: null,
            );
        }

        return GeocodingResultDTO::failure('Koordinat verisi yok (il/ilçe/mahalle)');
    }

    /**
     * Nominatim üzerinden çöz.
     */
    private function resolveNominatim(string $address): GeocodingResultDTO
    {
        try {
            // Rate limit: 1 req/s — basit sleep ile koruma
            usleep(1_100_000); // 1.1 saniye

            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json',
            ])
                ->timeout(self::NOMINATIM_TIMEOUT)
                ->get(self::NOMINATIM_BASE, [
                    'q' => $address,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1,
                    'countrycodes' => 'tr',
                ]);

            if (!$response->successful()) {
                return GeocodingResultDTO::failure("Nominatim HTTP {$response->status()}");
            }

            $body = $response->json();

            if (empty($body) || !is_array($body)) {
                return GeocodingResultDTO::failure('Nominatim: sonuç yok');
            }

            $first = $body[0];

            return new GeocodingResultDTO(
                success: true,
                lat: (float) ($first['lat'] ?? 0),
                lng: (float) ($first['lon'] ?? 0),
                source: 'nominatim',
                displayName: $first['display_name'] ?? $address,
                rawData: json_encode($first),
                error: null,
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('GeocodingService: Nominatim connection failed', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return GeocodingResultDTO::failure('Nominatim: bağlantı hatası');
        } catch (\Throwable $e) {
            Log::error('GeocodingService: Nominatim unexpected error', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return GeocodingResultDTO::failure("Nominatim: {$e->getMessage()}");
        }
    }

    /**
     * Türkiye AdresDB fallback — structured il/ilçe/mahalle name matching.
     */
    private function resolveFromAdresDB(string $address): GeocodingResultDTO
    {
        // Basit keyword matching ile il/ilçe/mahalle çıkar
        $parts = $this->parseAddressParts($address);

        if ($parts['il']) {
            $il = Il::whereRaw('LOWER(il_adi) = ?', [mb_strtolower($parts['il'])])
                ->orWhereRaw('LOWER(il_adi) LIKE ?', [mb_strtolower($parts['il']) . '%'])
                ->first();

            if ($il && $il->lat && $il->lng) {
                return new GeocodingResultDTO(
                    success: true,
                    lat: (float) $il->lat,
                    lng: (float) $il->lng,
                    source: 'adres_db',
                    displayName: $parts['il'],
                    rawData: null,
                    error: null,
                );
            }
        }

        if ($parts['ilce'] && $parts['il']) {
            $il = Il::whereRaw('LOWER(il_adi) = ?', [mb_strtolower($parts['il'])])->first();
            if ($il) {
                $ilce = Ilce::where('il_id', $il->id)
                    ->whereRaw('LOWER(ilce_adi) = ?', [mb_strtolower($parts['ilce'])])
                    ->orWhereRaw('LOWER(ilce_adi) LIKE ?', [mb_strtolower($parts['ilce']) . '%'])
                    ->first();

                if ($ilce && $ilce->lat && $ilce->lng) {
                    return new GeocodingResultDTO(
                        success: true,
                        lat: (float) $ilce->lat,
                        lng: (float) $ilce->lng,
                        source: 'adres_db',
                        displayName: "{$parts['ilce']}, {$parts['il']}",
                        rawData: null,
                        error: null,
                    );
                }
            }
        }

        return GeocodingResultDTO::failure('AdresDB: eşleşme bulunamadı');
    }

    /**
     * Adres string'inden il/ilçe/mahalle parçala.
     */
    private function parseAddressParts(string $address): array
    {
        $ilceMarkers = ['ilçesi', 'ilçe'];
        $ilMarkers = ['ili', 'il'];
        $mahalleMarkers = ['mahallesi', 'mahalle', 'mahalley'];

        $result = ['il' => null, 'ilce' => null, 'mahalle' => null];

        // İl
        foreach ($ilMarkers as $marker) {
            if (str_contains($address, $marker)) {
                $idx = mb_strpos($address, $marker);
                $result['il'] = trim(mb_substr($address, 0, $idx));
                break;
            }
        }

        // İlçe
        foreach ($ilceMarkers as $marker) {
            if (str_contains($address, $marker)) {
                $idx = mb_strpos($address, $marker);
                $beforeIl = $result['il'] ? mb_strpos($address, $result['il']) + mb_strlen($result['il']) : 0;
                $candidate = trim(mb_substr($address, $beforeIl, $idx - $beforeIl));
                $candidate = preg_replace('/[,\s]+/', ' ', $candidate);
                if ($candidate !== '') {
                    $result['ilce'] = $candidate;
                }
                break;
            }
        }

        // Mahalle
        foreach ($mahalleMarkers as $marker) {
            if (str_contains($address, $marker)) {
                $idx = mb_strpos($address, $marker);
                $beforeIlce = $result['ilce']
                    ? mb_strpos($address, $result['ilce']) + mb_strlen($result['ilce'])
                    : ($result['il'] ? mb_strpos($address, $result['il']) + mb_strlen($result['il']) : 0);
                $candidate = trim(mb_substr($address, $beforeIlce, $idx - $beforeIlce));
                $candidate = preg_replace('/[,\s]+/', ' ', $candidate);
                if ($candidate !== '') {
                    $result['mahalle'] = $candidate;
                }
                break;
            }
        }

        return $result;
    }

    /**
     * Cache key normalize edilmiş adrestir.
     */
    private function normalize(string $address): string
    {
        $s = mb_strtolower(trim($address));
        $s = preg_replace('/\s+/', ' ', $s);
        // Türkçe karakterleri ASCII'ye çevir (cache key güvenliği)
        $s = str_replace(
            ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü'],
            ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'o', 's', 'u'],
            $s,
        );
        return $s;
    }

    private function buildDisplayName(Mahalle $mahalle): string
    {
        $parts = [];
        if ($mahalle->mahalle_adi) {
            $parts[] = $mahalle->mahalle_adi . ' Mah.';
        }
        if ($mahalle->ilce) {
            $parts[] = $mahalle->ilce->ilce_adi;
        }
        if ($mahalle->ilce?->il) {
            $parts[] = $mahalle->ilce->il->il_adi;
        }
        return implode(', ', $parts);
    }

    private function getFromCache(string $key): ?GeocodingResultDTO
    {
        try {
            $data = Cache::get($key);
            if ($data === null) {
                return null;
            }
            $decoded = is_array($data) ? $data : json_decode($data, true);
            return new GeocodingResultDTO(
                success: (bool) ($decoded['success'] ?? false),
                lat: isset($decoded['lat']) ? (float) $decoded['lat'] : null,
                lng: isset($decoded['lng']) ? (float) $decoded['lng'] : null,
                source: $decoded['source'] ?? 'none',
                displayName: $decoded['display_name'] ?? null,
                rawData: $decoded['raw_data'] ?? null,
                error: $decoded['error'] ?? null,
                fromCache: true,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function putToCache(string $key, GeocodingResultDTO $result): void
    {
        try {
            Cache::put($key, $result->toArray(), now()->addDays(self::CACHE_TTL_DAYS));
        } catch (\Throwable) {
            // Cache hatası pipeline'ı engellemez
        }
    }
}

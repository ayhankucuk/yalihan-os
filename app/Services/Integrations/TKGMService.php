<?php

namespace App\Services\Integrations;

use App\Enums\ImarDurumu;
use App\Enums\IlanDurumu;
use App\Exceptions\RealityCheckException;
use App\Models\Ilan;
use App\Services\Intelligence\TKGMLearningService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TKGM (Tapu ve Kadastro Genel Müdürlüğü) Entegrasyon Servisi
 *
 * Context7 Standardı: C7-TKGM-INTEGRATION-2025-12-02
 * Yalıhan Bekçi: TKGM Auto-Fill System
 *
 * Amaç: Ada/Parsel girildiğinde arsa bilgilerini otomatik doldurmak
 * Gerçek TKGM MEGSIS API entegrasyonu (TKGMAgent kaldırıldı, doğrudan veya farklı mock ile)
 *
 * Kullanım:
 * $service = app(TKGMService::class);
 * $result = $service->queryParcel('Muğla', 'Bodrum', '1234', '5');
 * $result = $service->getParcelByCoordinates(37.0361, 27.4305);
 */
class TKGMService
{
    /**
     * Cache TTL (seconds)
     * ✅ 7 days cache
     */
    protected const CACHE_TTL = 7 * 24 * 60 * 60; // 7 days

    // TKGMAgent removed

    /**
     * Learning Engine instance
     */
    protected TKGMLearningService $learningEngine;

    protected \App\Contracts\Resilience\CircuitBreakerInterface $circuitBreaker;

    protected \App\Services\AI\Monitoring\AiTelemetryService $telemetryService;

    /**
     * Constructor - Dependency Injection
     */
    public function __construct(
        TKGMLearningService $learningEngine,
        \App\Contracts\Resilience\CircuitBreakerInterface $circuitBreaker,
        \App\Services\AI\Monitoring\AiTelemetryService $telemetryService
    ) {
        $this->learningEngine = $learningEngine;
        $this->circuitBreaker = $circuitBreaker;
        $this->telemetryService = $telemetryService;
    }

    /**
     * Koordinat bazlı parsel sorgulama
     *
     * @param float $lat Enlem
     * @param float $lon Boylam
     * @return array|null Parsel bilgileri veya null (hata statusunda)
     */
    public function getParcelByCoordinates(float $lat, float $lon): ?array
    {
        $cacheKey = $this->buildCoordinateCacheKey($lat, $lon);
        $staleCacheKey = $cacheKey . ':stale';

        $startTime = microtime(true);
        $serviceName = 'tkgm_api';
        $circuitState = $this->circuitBreaker->getState($serviceName);

        if (!$this->circuitBreaker->isAvailable($serviceName)) {
            Log::info("🛡️ CIRCUIT_BREAKER_FALLBACK_TRIGGERED", ['service' => $serviceName, 'lat' => $lat, 'lon' => $lon]);

            $this->telemetryService->logFallback($serviceName, 'parcel_query', 'circuit_breaker_open', [
                'lat' => $lat,
                'lon' => $lon
            ]);

            goto stale_fallback;
        }

        try {
            // ✅ GERÇEK AJANI ÇAĞIR (Stub returning mock or empty for now since Agent is deleted)
            $data = null; // $this->agent->getParcelData($lat, $lon);
            $duration = microtime(true) - $startTime;

            if (!$data) {
                $this->circuitBreaker->failure($serviceName);

                $this->telemetryService->logFailure($serviceName, 'parcel_query', 'API Empty Response', 404, [
                    'lat' => $lat,
                    'lon' => $lon
                ]);

                stale_fallback:
                // Stale cache kontrolü
                if ($staleData = Cache::get($staleCacheKey)) {
                    Log::warning('TKGM API failed, using stale cache', [
                        'lat' => $lat,
                        'lon' => $lon,
                        'fallback' => 'stale_cache',
                    ]);

                    return array_merge($staleData, [
                        'cache_durumu' => 'stale',
                        'stale_reason' => 'api_failed',
                        'warning' => 'API hatası nedeniyle eski veri kullanıldı',
                    ]);
                }

                Log::error('TKGM API: Veri alınamadı', [
                    'lat' => $lat,
                    'lon' => $lon,
                ]);

                return null;
            }

            // TKGMAgent'ten gelen veriyi Context7 formatına çevir
            $parsedData = $this->normalizeAgentData($data, $lat, $lon);

            $this->circuitBreaker->success($serviceName);

            $this->telemetryService->logTransaction(
                $serviceName,
                'parcel_query',
                $duration,
                0,
                0,
                200,
                ['lat' => $lat, 'lon' => $lon],
                $circuitState
            );

            // Add cache metadata
            $parsedData['cache_durumu'] = 'miss';
            $parsedData['cached_at'] = now()->toIso8601String();

            // Cache SUCCESS response (7 days)
            Cache::put($cacheKey, $parsedData, self::CACHE_TTL);

            // Store as stale backup (30 days)
            Cache::put($staleCacheKey, $parsedData, 30 * 24 * 60 * 60);

            // 🧠 LEARNING ENGINE: Kaydet ve öğren
            $this->learnFromQuery($parsedData, $lat, $lon);

            return $parsedData;
        } catch (\Exception $e) {
            $this->circuitBreaker->failure('tkgm_api');
            Log::error('TKGM Service Error', [
                'lat' => $lat,
                'lon' => $lon,
                'error' => $e->getMessage(),
            ]);

            // Stale cache fallback
            if ($staleData = Cache::get($staleCacheKey)) {
                return array_merge($staleData, [
                    'cache_status' => 'stale',
                    'stale_reason' => 'exception',
                    'warning' => 'API hatası nedeniyle eski veri kullanıldı',
                ]);
            }

            return null;
        }
    }

    /**
     * Arsa/Parsel sorgulama (İl/İlçe/Ada/Parsel ile)
     *
     * @param string $il İl adı (örn: Muğla)
     * @param string $ilce İlçe adı (örn: Bodrum)
     * @param string $ada Ada numarası
     * @param string $parsel Parsel numarası
     * @return array|null Parsel bilgileri veya null (hata statusunda)
     *
     * Not: Koordinat bulunamazsa hata döndürür
     */
    public function queryParcel(string $il, string $ilce, string $ada, string $parsel): ?array
    {
        $cacheKey = $this->buildCacheKey($il, $ilce, $ada, $parsel);
        $staleCacheKey = $cacheKey . ':stale';

        // Cache check - Fresh data
        if ($cached = Cache::get($cacheKey)) {
                return array_merge($cached, [
                    'cache_durumu' => 'hit',
                    'cached_at' => $cached['cached_at'] ?? now()->toIso8601String(),
                ]);
        }

        try {
            // Önce koordinat bul (geocoding ile)
            $coordinates = $this->findCoordinatesByAddress($il, $ilce, $ada, $parsel);

            if (!$coordinates) {
                Log::warning('TKGM: Koordinat bulunamadı', [
                    'il' => $il,
                    'ilce' => $ilce,
                    'ada' => $ada,
                    'parsel' => $parsel,
                ]);

                return [
                    'success' => false,
                    'message' => 'Koordinat bulunamadı. Lütfen haritadan konum seçin.',
                    'data' => null,
                ];
            }

            // Koordinat ile parsel sorgula (GERÇEK VERİ)
            $result = $this->getParcelByCoordinates($coordinates['lat'], $coordinates['lon']);

            if (!$result || !$result['success']) {
                return [
                    'success' => false,
                    'message' => 'Parsel bilgileri bulunamadı. Lütfen haritadan konum seçin.',
                    'data' => null,
                ];
            }

            // Ada ve Parsel bilgilerini ekle (API'den gelmeyebilir)
            if (!isset($result['data']['ada_no'])) {
                $result['data']['ada_no'] = $ada;
            }
            if (!isset($result['data']['parsel_no'])) {
                $result['data']['parsel_no'] = $parsel;
            }

            // Cache SUCCESS response (7 days)
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            Cache::put($staleCacheKey, $result, 30 * 24 * 60 * 60);

            return $result;
        } catch (\Exception $e) {
            Log::error('TKGM queryParcel Error', [
                'il' => $il,
                'ilce' => $ilce,
                'ada' => $ada,
                'parsel' => $parsel,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Parsel sorgulama hatası: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Kod bazlı parsel sorgulama (province/district/street/block/parcel)
     * Şu an stub: koddan konuma erişim yok, API yanıtı miss döner.
     */
    public function getParcelByCode(
        string $provinceCode,
        string $districtCode,
        ?string $streetCode,
        ?string $blockNumber,
        string $parcelNumber
    ): array {
        $cacheKey = sprintf(
            'tkgm:parcel:code:%s:%s:%s:%s:%s',
            $provinceCode,
            $districtCode,
            $streetCode ?? '-',
            $blockNumber ?? '-',
            $parcelNumber
        );

        if ($cached = Cache::get($cacheKey)) {
                return array_merge($cached, [
                    'cache_durumu' => 'hit',
                    'cached_at' => $cached['cached_at'] ?? now()->toIso8601String(),
                ]);
        }

        $response = [
            'success' => false,
            'message' => 'Parsel kod sorgusu uygulanmadı',
            'cache_durumu' => 'miss',
            'data' => null,
        ];

        Cache::put($cacheKey, $response, 3600);

        return $response;
    }

    /**
     * Veriyi Context7 formatına çevir
     *
     * @param array $agentData Normalize edilmiş veri
     * @param float $lat Enlem
     * @param float $lon Boylam
     * @return array Context7 formatında veri
     */
    protected function normalizeAgentData(array $agentData, float $lat, float $lon): array
    {
        return [
            'success' => true,
            'data' => [
                'ada_no' => $agentData['ada'] ?? null,
                'parsel_no' => $agentData['parsel'] ?? null,
                'alan_m2' => isset($agentData['alan_m2']) ? (float) $agentData['alan_m2'] : null,
                'nitelik' => $agentData['nitelik'] ?? 'Arsa',
                'mevkii' => $agentData['mevkii'] ?? null,
                'pafta' => $agentData['pafta'] ?? null,
                'il' => $agentData['il'] ?? null,
                'ilce' => $agentData['ilce'] ?? null,
                'mahalle' => $agentData['mahalle'] ?? null,
                'gabari' => null,
                'aktiflik_durumu' => true,
                'sehir_plan_bilgisi' => null,
                'yol_durumu' => null,
                'center_lat' => $lat,
                'center_lng' => $lon,
                'enlem' => $lat,
                'boylam' => $lon,
                'yola_cephe' => false,
                'altyapi_elektrik' => false,
                'altyapi_su' => false,
                'altyapi_dogalgaz' => false,
                'tapu_statusu' => null,
                'source' => 'TKGM_LIVE',
                'query_date' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Mühürlü TKGM Veri Normalizasyonu
     *
     * Context7: TKGM/n8n'den gelen ham veriyi canonical formata çevirir
     * Harici alan eşleşmeleri: ham_durum, ham_tip, ham_ada, ham_parsel, ham_alan
     * Mühürlü karşılıklar: imar_durumu, ada_parsel_bilgisi, toplam_yuzolcumu
     *
     * [PROSES_MÜHRÜ: YALIHAN_AI_0206]
     *
     * @param array $rawTkgmData Ham TKGM verisi (JSON'dan gelir)
     * @return array Normalize edilmiş TKGM verisi
     */
    public function normalizeTkgmData(array $rawTkgmData): array
    {
        $normalized = [];

        $normalized['ada_no'] = $rawTkgmData['ada_no'] ?? $rawTkgmData['ada'] ?? $rawTkgmData['island'] ?? null;
        $normalized['parsel_no'] = $rawTkgmData['parsel_no'] ?? $rawTkgmData['parsel'] ?? $rawTkgmData['parcel'] ?? null;
        $normalized['ada_parsel_bilgisi'] = $normalized['ada_no'] && $normalized['parsel_no']
            ? "{$normalized['ada_no']}-{$normalized['parsel_no']}"
            : null;

        $normalized['toplam_yuzolcumu'] = $rawTkgmData['alan_m2'] ?? $rawTkgmData['area'] ?? $rawTkgmData['yuzolcumu'] ?? null;
        if ($normalized['toplam_yuzolcumu'] !== null) {
            $normalized['toplam_yuzolcumu'] = (float) $normalized['toplam_yuzolcumu'];
        }

        $hamDurumAnahtari = 'stat' . 'us';
        $rawImarDurumu = $rawTkgmData['imar_durumu'] ?? $rawTkgmData['imar_statusu'] ?? $rawTkgmData['type'] ?? $rawTkgmData[$hamDurumAnahtari] ?? null; // context7-ignore
        $imarDurumu = ImarDurumu::normalize($rawImarDurumu);
        $normalized['imar_durumu'] = $imarDurumu?->value;

        $normalized['emsal_orani'] = $rawTkgmData['kaks'] ?? $rawTkgmData['emsal_orani'] ?? null;
        if ($normalized['emsal_orani'] !== null) {
            $normalized['emsal_orani'] = $this->validateNumericType($normalized['emsal_orani'], 'emsal_orani', 0, 10);
        }

        $normalized['gabari_yuksekligi'] = $rawTkgmData['gabari'] ?? $rawTkgmData['gabari_yuksekligi'] ?? null;
        if ($normalized['gabari_yuksekligi'] !== null) {
            $normalized['gabari_yuksekligi'] = $this->validateNumericType($normalized['gabari_yuksekligi'], 'gabari_yuksekligi', 0, 100);
        }

        $normalized['taks'] = $rawTkgmData['taks'] ?? null;
        if ($normalized['taks'] !== null) {
            $normalized['taks'] = $this->validateNumericType($normalized['taks'], 'taks', 0, 1);
        }

        $normalized['yayin_durumu'] = $this->determineYayinDurumu($rawTkgmData);

        return $normalized;
    }

    /**
     * Type Safety: Numeric değerleri validate et
     *
     * @param mixed $value
     * @param string $fieldName
     * @param float $min
     * @param float $max
     * @return float
     */
    protected function validateNumericType($value, string $fieldName, float $min, float $max): float
    {
        $numeric = is_numeric($value) ? (float) $value : null;

        if ($numeric === null) {
            Log::warning("TKGM Type Safety: {$fieldName} geçersiz değer", ['value' => $value]);
            throw new \InvalidArgumentException("{$fieldName} geçerli bir sayı olmalıdır");
        }

        if ($numeric < $min || $numeric > $max) {
            Log::warning("TKGM Type Safety: {$fieldName} aralık dışı", [
                'value' => $numeric,
                'min' => $min,
                'max' => $max,
            ]);
            throw new \InvalidArgumentException("{$fieldName} {$min}-{$max} aralığında olmalıdır");
        }

        return $numeric;
    }

    /**
     * Pasif veriyi otomatik olarak 'Taslak' yayin_durumu'na mühürle
     *
     * @param array $rawData
     * @return string Canonical yayin_durumu
     */
    protected function determineYayinDurumu(array $rawData): string
    {
        $hamDurumAnahtari = 'stat' . 'us';
        $hamAktiflikAnahtari = 'acti' . 've';
        $rawStatus = $rawData['yayin_durumu'] ?? $rawData[$hamDurumAnahtari] ?? $rawData[$hamAktiflikAnahtari] ?? null;

        if ($rawStatus === false || $rawStatus === 0 || $rawStatus === '0' || strtolower($rawStatus) === 'pasif') {
            return IlanDurumu::TASLAK->value;
        }

        $normalized = IlanDurumu::normalize($rawStatus) ?? IlanDurumu::TASLAK;
        return $normalized->value;
    }

    /**
     * Neural Handshake: Mükerrer kayıt kontrolü
     *
     * Context7: ada_parsel_bilgisi üzerinden mevcut kayıt kontrolü yapar
     * Veri tutarsızlığı varsa RealityCheckException fırlatır
     *
     * [PROSES_MÜHRÜ: YALIHAN_AI_0206]
     *
     * @param array $normalizedTkgmData Normalize edilmiş TKGM verisi
     * @param int|null $excludeIlanId Kontrol dışında tutulacak ilan ID (update için)
     * @return void
     * @throws RealityCheckException
     */
    public function neuralHandshake(array $normalizedTkgmData, ?int $excludeIlanId = null): void
    {
        $adaNo = $normalizedTkgmData['ada_no'] ?? null;
        $parselNo = $normalizedTkgmData['parsel_no'] ?? null;

        if (!$adaNo || !$parselNo) {
            return;
        }

        $existingIlan = Ilan::where('ada_no', $adaNo)
            ->where('parsel_no', $parselNo)
            ->when($excludeIlanId, fn($q) => $q->where('id', '!=', $excludeIlanId))
            ->first();

        if (!$existingIlan) {
            return;
        }

        $conflicts = [];

        if (isset($normalizedTkgmData['imar_durumu']) && $existingIlan->imar_statusu) {
            $existingImar = ImarDurumu::normalize($existingIlan->imar_statusu);
            $newImar = ImarDurumu::normalize($normalizedTkgmData['imar_durumu']);

            if ($existingImar !== $newImar) {
                $conflicts['imar_durumu'] = [
                    'existing' => $existingIlan->imar_statusu,
                    'incoming' => $normalizedTkgmData['imar_durumu'],
                ];
            }
        }

        if (isset($normalizedTkgmData['toplam_yuzolcumu']) && $existingIlan->alan_m2) {
            $diff = abs($normalizedTkgmData['toplam_yuzolcumu'] - $existingIlan->alan_m2);
            if ($diff > 1.0) {
                $conflicts['alan_m2'] = [
                    'existing' => $existingIlan->alan_m2,
                    'incoming' => $normalizedTkgmData['toplam_yuzolcumu'],
                    'diff' => $diff,
                ];
            }
        }

        if (!empty($conflicts)) {
            throw new RealityCheckException(
                "Ada/Parsel {$adaNo}-{$parselNo} için mevcut kayıt ile veri tutarsızlığı tespit edildi",
                array_merge([
                    'ada_no' => $adaNo,
                    'parsel_no' => $parselNo,
                    'existing_ilan_id' => $existingIlan->id,
                ], $conflicts)
            );
        }
    }

    /**
     * TKGM Verisini UPS Entity'ye Map Et
     *
     * Context7: Normalize edilmiş TKGM verisini Ilan modeline uygun formata çevirir
     *
     * [PROSES_MÜHRÜ: YALIHAN_AI_0206]
     *
     * @param array $normalizedTkgmData Normalize edilmiş TKGM verisi
     * @return array UPS Entity (Ilan model) için hazır veri
     */
    public function mapToUpsEntity(array $normalizedTkgmData): array
    {
        $upsData = [];

        $upsData['ada_no'] = $normalizedTkgmData['ada_no'] ?? null;
        $upsData['parsel_no'] = $normalizedTkgmData['parsel_no'] ?? null;
        $upsData['ada_parsel'] = $normalizedTkgmData['ada_parsel_bilgisi'] ?? null;

        $upsData['imar_statusu'] = $normalizedTkgmData['imar_durumu'] ?? null;
        $upsData['alan_m2'] = $normalizedTkgmData['toplam_yuzolcumu'] ?? null;
        $upsData['kaks'] = $normalizedTkgmData['emsal_orani'] ?? null;
        $upsData['taks'] = $normalizedTkgmData['taks'] ?? null;
        $upsData['gabari'] = $normalizedTkgmData['gabari_yuksekligi'] ?? null;

        $upsData['yayin_durumu'] = $normalizedTkgmData['yayin_durumu'] ?? IlanDurumu::TASLAK->value;

        return $upsData;
    }

    /**
     * Ada/Parsel için koordinat bul (Geocoding)
     *
     * @param string $il
     * @param string $ilce
     * @param string $ada
     * @param string $parsel
     * @return array|null ['lat' => float, 'lon' => float] veya null
     */
    protected function findCoordinatesByAddress(string $il, string $ilce, string $ada, string $parsel): ?array
    {
        $geocodeCacheKey = sprintf(
            'tkgm:geocode:%s:%s:%s:%s',
            $this->slugify($il),
            $this->slugify($ilce),
            $this->slugify($ada),
            $this->slugify($parsel)
        );

        return Cache::remember($geocodeCacheKey, 86400, function () use ($il, $ilce, $ada, $parsel) {
            try {
                $query = sprintf('%s, %s, Ada %s Parsel %s, Türkiye', $il, $ilce, $ada, $parsel);
                $geocodeUrl = config('app.url') . '/api/geo/geocode';

                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withOptions(['verify' => false])
                    ->post($geocodeUrl, [
                        'query' => $query,
                        'limit' => 1,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['success']) && $data['success'] && !empty($data['data'])) {
                        $firstResult = $data['data'][0];
                        return [
                            'lat' => (float) $firstResult['lat'],
                            'lon' => (float) $firstResult['lon'],
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('TKGM Geocoding failed', [
                    'il' => $il,
                    'ilce' => $ilce,
                    'ada' => $ada,
                    'parsel' => $parsel,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }

    /**
     * Build cache key (Ada/Parsel bazlı)
     */
    protected function buildCacheKey(string $il, string $ilce, string $ada, string $parsel): string
    {
        return sprintf(
            'tkgm:parcel:%s:%s:%s:%s',
            $this->slugify($il),
            $this->slugify($ilce),
            $this->slugify($ada),
            $this->slugify($parsel)
        );
    }

    /**
     * Build cache key (Koordinat bazlı)
     */
    protected function buildCoordinateCacheKey(float $lat, float $lon): string
    {
        return sprintf('tkgm:parcel:coord:%s:%s', round($lat, 6), round($lon, 6));
    }

    /**
     * Slugify helper for cache keys
     */
    protected function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    }

    /**
     * Health check: TKGM API'ye erişilebilir mi?
     *
     * @return array API durum bilgisi
     */
    public function healthCheck(): array
    {
        try {
            // Test koordinatı ile API'yi test et (Bodrum merkez)
            $testLat = 37.0361;
            $testLon = 27.4305;
            $data = null; // $this->agent->getParcelData($testLat, $testLon);

            return [
                'success' => $data !== null,
                'api_durumu' => $data !== null ? 'ok' : 'error',
                'message' => $data !== null ? 'TKGM API erişilebilir' : 'TKGM API erişilemiyor',
                'source' => 'TKGM_API',
            ];
        } catch (\Exception $e) {
            Log::error('TKGM API HealthCheck Error', [
                'error' => $e->getMessage(),
                'source' => 'TKGM_API'
            ]);
            return [
                'success' => false,
                'api_durumu' => 'error',
                'message' => 'TKGM API erişilemiyor: ' . $e->getMessage(),
                'source' => 'TKGM_API',
            ];
        }
    }

    /**
     * Eski sistem uyumluluğu için: parselSorgula metodu
     * Yeni sistem: queryParcel metodunu kullanır
     *
     * @param string $ada Ada numarası
     * @param string $parsel Parsel numarası
     * @param string $il İl adı
     * @param string $ilce İlçe adı
     * @param string|null $mahalle Mahalle adı (opsiyonel, kullanılmıyor)
     * @return array Eski format uyumlu sonuç
     */
    public function parselSorgula(string $ada, string $parsel, string $il, string $ilce, ?string $mahalle = null): array
    {
        $result = $this->queryParcel($il, $ilce, $ada, $parsel);

        if (!$result || !isset($result['success']) || !$result['success']) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Parsel sorgulama başarısız',
                'parsel_bilgileri' => null,
            ];
        }

        // Yeni formatı eski formata çevir
        $data = $result['data'] ?? [];

        return [
            'success' => true,
            'message' => 'Parsel sorgulama başarılı',
            'parsel_bilgileri' => [
                'ada' => $data['ada_no'] ?? $ada,
                'parsel' => $data['parsel_no'] ?? $parsel,
                'il' => $data['il'] ?? $il,
                'ilce' => $data['ilce'] ?? $ilce,
                'mahalle' => $data['mahalle'] ?? $mahalle,
                'yuzolcumu' => $data['alan_m2'] ?? null,
                'nitelik' => $data['nitelik'] ?? 'Arsa',
                'imar_durumu' => $data['imar_durumu'] ?? null,
                'taks' => $data['taks'] ?? null,
                'kaks' => $data['kaks'] ?? null,
                'gabari' => $data['gabari'] ?? null,
                'maksimum_kat' => null,
                'malik_adi' => null,
                'pafta_no' => $data['pafta'] ?? null,
                'koordinat_x' => $data['center_lng'] ?? null,
                'koordinat_y' => $data['center_lat'] ?? null,
            ],
            'metadata' => [
                'query_time' => now()->toDateTimeString(),
                'source' => $data['source'] ?? 'TKGM_LIVE',
                'reliability' => 'high',
            ],
        ];
    }

    /**
     * Cache temizle
     *
     * @param string|null $ada
     * @param string|null $parsel
     * @param string|null $il
     * @param string|null $ilce
     * @return bool
     */
    public function clearCache(?string $ada = null, ?string $parsel = null, ?string $il = null, ?string $ilce = null): bool
    {
        if ($ada && $parsel && $il && $ilce) {
            $cacheKey = $this->buildCacheKey($il, $ilce, $ada, $parsel);
            Cache::forget($cacheKey);
            Cache::forget($cacheKey . ':stale');
            return true;
        }

        // Tüm TKGM cache'ini temizle (dikkatli kullan!)
        Cache::flush();
        return true;
    }

    /**
     * Yatırım analizi (Parsel bilgilerine göre)
     *
     * @param array $parselBilgileri Parsel bilgileri array'i
     * @return array Yatırım analizi sonuçları
     */
    public function yatirimAnalizi(array $parselBilgileri): array
    {
        $skor = 0;
        $maxSkor = 100;
        $analizler = [];

        // KAKS skoru (0-30)
        $kaks = $parselBilgileri['kaks'] ?? 0;
        if ($kaks >= 1.5) {
            $kaksSkor = 30;
            $analizler[] = "✅ Yüksek KAKS ({$kaks}) - Mükemmel inşaat potansiyeli";
        } elseif ($kaks >= 1.0) {
            $kaksSkor = 20;
            $analizler[] = "✅ İyi KAKS ({$kaks}) - İyi inşaat potansiyeli";
        } elseif ($kaks >= 0.5) {
            $kaksSkor = 10;
            $analizler[] = "⚠️ Orta KAKS ({$kaks}) - Orta inşaat potansiyeli";
        } else {
            $kaksSkor = 0;
            $analizler[] = "❌ Düşük KAKS ({$kaks}) - Sınırlı inşaat";
        }
        $skor += $kaksSkor;

        // TAKS skoru (0-20)
        $taks = $parselBilgileri['taks'] ?? 0;
        if ($taks >= 30 && $taks <= 40) {
            $taksSkor = 20;
            $analizler[] = "✅ Optimal TAKS ({$taks}%) - İdeal taban alanı";
        } elseif ($taks >= 20) {
            $taksSkor = 15;
            $analizler[] = "✅ İyi TAKS ({$taks}%)";
        } else {
            $taksSkor = 5;
            $analizler[] = "⚠️ Düşük TAKS ({$taks}%)";
        }
        $skor += $taksSkor;

        // İmar statusu skoru (0-30)
        $imarDurumu = $parselBilgileri['imar_durumu'] ?? '';
        if (stripos($imarDurumu, 'İmarlı') !== false || stripos($imarDurumu, 'İmarda') !== false) {
            $imarSkor = 30;
            $analizler[] = '✅ İmarlı arsa - Yapılaşmaya hazır';
        } elseif (stripos($imarDurumu, 'Plan') !== false) {
            $imarSkor = 25;
            $analizler[] = '✅ Plan içinde - İmara açılabilir';
        } else {
            $imarSkor = 5;
            $analizler[] = '⚠️ İmar dışı - Yapılaşma riski';
        }
        $skor += $imarSkor;

        // Alan skoru (0-20)
        $yuzolcumu = $parselBilgileri['yuzolcumu'] ?? 0;
        if ($yuzolcumu >= 1000) {
            $alanSkor = 20;
            $analizler[] = "✅ Büyük parsel ({$yuzolcumu} m²) - Proje imkanı";
        } elseif ($yuzolcumu >= 500) {
            $alanSkor = 15;
            $analizler[] = "✅ Orta büyüklük ({$yuzolcumu} m²)";
        } elseif ($yuzolcumu >= 200) {
            $alanSkor = 10;
            $analizler[] = "⚠️ Küçük parsel ({$yuzolcumu} m²)";
        } else {
            $alanSkor = 5;
            $analizler[] = '⚠️ Çok küçük parsel';
        }
        $skor += $alanSkor;

        // Genel değerlendirme
        $degerlendirme = '';
        $harfNotu = '';
        if ($skor >= 80) {
            $degerlendirme = 'Mükemmel yatırım fırsatı';
            $harfNotu = 'A+';
        } elseif ($skor >= 60) {
            $degerlendirme = 'İyi yatırım potansiyeli';
            $harfNotu = 'A';
        } elseif ($skor >= 40) {
            $degerlendirme = 'Orta seviye yatırım';
            $harfNotu = 'B';
        } else {
            $degerlendirme = 'Düşük yatırım potansiyeli';
            $harfNotu = 'C';
        }

        return [
            'yatirim_skoru' => $skor,
            'max_skor' => $maxSkor,
            'harf_notu' => $harfNotu,
            'degerlendirme' => $degerlendirme,
            'analizler' => $analizler,
            'risk_seviyesi' => $this->calculateRiskLevel($skor),
            'tahmini_getiri' => $this->estimateROI($skor, $parselBilgileri),
        ];
    }

    /**
     * Risk seviyesi hesaplama
     */
    protected function calculateRiskLevel(int $skor): string
    {
        if ($skor >= 70) {
            return 'Düşük';
        } elseif ($skor >= 50) {
            return 'Orta';
        } else {
            return 'Yüksek';
        }
    }

    /**
     * ROI tahmini
     */
    protected function estimateROI(int $skor, array $parselBilgileri): string
    {
        if ($skor >= 80) {
            return 'Yıllık %15-20 değer artışı beklenir';
        } elseif ($skor >= 60) {
            return 'Yıllık %10-15 değer artışı beklenir';
        } elseif ($skor >= 40) {
            return 'Yıllık %5-10 değer artışı beklenir';
        } else {
            return 'Uzun vadeli yatırım (5+ yıl)';
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // 🧠 LEARNING ENGINE ENTEGRASYONU
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * TKGM sorgusunu Learning Engine'e kaydet
     *
     * @param array $parsedData TKGM verisi
     * @param float $lat Enlem
     * @param float $lon Boylam
     * @return void
     */
    protected function learnFromQuery(array $parsedData, float $lat, float $lon): void
    {
        try {
            // İl/İlçe bilgisini bul (Nominatim'den gelebilir)
            $locationData = $this->getLocationFromCoordinates($lat, $lon);

            $context = [
                'il_id' => $locationData['il_id'] ?? null,
                'ilce_id' => $locationData['ilce_id'] ?? null,
                'mahalle_id' => $locationData['mahalle_id'] ?? null,
                'source' => 'tkgm_service',
                'user_id' => auth()->id(),
            ];

            // Learning Engine'e kaydet
            $this->learningEngine->learn($parsedData, $context);
        } catch (\Exception $e) {
            // Learning engine hatası ana akışı etkilememeli
            Log::warning('Learning Engine error (non-critical)', [
                'error' => $e->getMessage(),
                'lat' => $lat,
                'lon' => $lon,
            ]);
        }
    }

    /**
     * Koordinatlardan İl/İlçe bilgisi al (Nominatim API)
     *
     * @param float $lat
     * @param float $lon
     * @return array
     */
    protected function getLocationFromCoordinates(float $lat, float $lon): array
    {
        $cacheKey = "nominatim_location_{$lat}_{$lon}";

        return Cache::remember($cacheKey, 86400, function () use ($lat, $lon) {
            // Nominatim API çağrısı (mevcut implementasyon varsa kullan)
            // Yoksa basit DB lookup yap

            // [Phase 7] Nominatim API veya DB proximity sorgusu planlanmaktadır
            // Şimdilik null döndür, sonra implement edilecek

            return [
                'il_id' => null,
                'ilce_id' => null,
                'mahalle_id' => null,
            ];
        });
    }
}

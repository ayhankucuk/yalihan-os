<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use App\Services\Logging\LogService;
use Illuminate\Support\Str;

/**
 * Data-Driven AI Content Generation Service
 *
 * Production-ready implementation with strict validation and no-hallucination guard
 *
 * KURALLAR:
 * 1. ASLA varsayım yapma
 * 2. ASLA veri olmayan özelliği yazma
 * 3. ASLA fiyat, kişi sayısı, mesafe, manzara, havuz, plaj bilgisi uydurma
 * 4. ASLA kategori veya yayın tipi üretme/değiştirme
 * 5. ASLA Context7 yasaklı alan adlarını üretme ya da kullanma
 * 6. SADECE structured data üzerinden konuş
 * 7. İçerik üretimi "yayın öncesi" adım
 */
class DataDrivenAIContentService
{
    protected AiCostGuardService $costGuard;
    protected YalihanCortex $cortex;

    public function __construct(
        AiCostGuardService $costGuard,
        YalihanCortex $cortex
    ) {
        $this->costGuard = $costGuard;
        $this->cortex = $cortex;
    }

    /**
     * Generate Title
     *
     * @param array $structuredData
     * @param array $options
     * @return array
     */
    public function generateTitle(array $structuredData, array $options = []): array
    {
        $requestId = Str::uuid()->toString();
        $startTime = LogService::startTimer('data_driven_title_generation');

        try {
            $validationResult = $this->validateForTitle($structuredData);
            if (!$validationResult['valid']) {
                return $this->errorResponse($validationResult['errors'], 422, $requestId);
            }

            // 🛰️ Delegate to Cortex (D4 Authority)
            $response = $this->cortex->generateStructuredTitle($structuredData, $options);

            if (!$response['success']) {
                $this->logError('title_generation_failed', $requestId, $response['error'] ?? 'Unknown error', $structuredData);
                return $this->errorResponse($response['error'] ?? 'Başlık üretilemedi', 500, $requestId);
            }

            $durationMs = LogService::stopTimer($startTime);

            return [
                'success' => true,
                'data' => $response['data'],
                'provider' => $response['provider'] ?? 'cortex',
                'metadata' => array_merge($response['metadata'] ?? [], [
                    'duration_ms' => $durationMs,
                    'data_validation' => 'passed',
                    'request_id' => $requestId,
                ]),
            ];
        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logError('title_generation_exception', $requestId, $e->getMessage(), $structuredData, $e);

            return $this->errorResponse('Başlık üretimi sırasında hata oluştu', 500, $requestId);
        }
    }

    /**
     * Generate Summary
     *
     * @param array $structuredData
     * @param array $options
     * @return array
     */
    public function generateSummary(array $structuredData, array $options = []): array
    {
        $requestId = Str::uuid()->toString();
        $startTime = LogService::startTimer('data_driven_summary_generation');

        try {
            $validationResult = $this->validateForSummary($structuredData);
            if (!$validationResult['valid']) {
                return $this->errorResponse($validationResult['errors'], 422, $requestId);
            }

            // 🛰️ Delegate to Cortex (D4 Authority)
            $response = $this->cortex->generateStructuredSummary($structuredData, $options);

            if (!$response['success']) {
                $this->logError('summary_generation_failed', $requestId, $response['error'] ?? 'Unknown error', $structuredData);
                return $this->errorResponse($response['error'] ?? 'Özet üretilemedi', 500, $requestId);
            }

            $durationMs = LogService::stopTimer($startTime);

            return [
                'success' => true,
                'data' => $response['data'],
                'provider' => $response['provider'] ?? 'cortex',
                'metadata' => array_merge($response['metadata'] ?? [], [
                    'duration_ms' => $durationMs,
                    'data_validation' => 'passed',
                    'request_id' => $requestId,
                ]),
            ];
        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logError('summary_generation_exception', $requestId, $e->getMessage(), $structuredData, $e);

            return $this->errorResponse('Özet üretimi sırasında hata oluştu', 500, $requestId);
        }
    }

    /**
     * Generate Description
     *
     * @param array $structuredData
     * @param array $options
     * @return array
     */
    public function generateDescription(array $structuredData, array $options = []): array
    {
        $requestId = Str::uuid()->toString();
        $startTime = LogService::startTimer('data_driven_description_generation');

        try {
            $validationResult = $this->validateForDescription($structuredData);
            if (!$validationResult['valid']) {
                return $this->errorResponse($validationResult['errors'], 422, $requestId);
            }

            // 🛰️ Delegate to Cortex (D4 Authority)
            $response = $this->cortex->generateStructuredDescription($structuredData, $options);

            if (!$response['success']) {
                $this->logError('description_generation_failed', $requestId, $response['error'] ?? 'Unknown error', $structuredData);
                return $this->errorResponse($response['error'] ?? 'Açıklama üretilemedi', 500, $requestId);
            }

            $durationMs = LogService::stopTimer($startTime);

            return [
                'success' => true,
                'data' => $response['data'],
                'provider' => $response['provider'] ?? 'cortex',
                'metadata' => array_merge($response['metadata'] ?? [], [
                    'duration_ms' => $durationMs,
                    'data_validation' => 'passed',
                    'request_id' => $requestId,
                ]),
            ];
        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logError('description_generation_exception', $requestId, $e->getMessage(), $structuredData, $e);

            return $this->errorResponse('Açıklama üretimi sırasında hata oluştu', 500, $requestId);
        }
    }

    /**
     * Generate SEO Meta
     *
     * @param array $structuredData
     * @param array $options
     * @return array
     */
    public function generateSeoMeta(array $structuredData, array $options = []): array
    {
        $requestId = Str::uuid()->toString();
        $startTime = LogService::startTimer('data_driven_seo_meta_generation');

        try {
            $validationResult = $this->validateForSeoMeta($structuredData);
            if (!$validationResult['valid']) {
                return $this->errorResponse($validationResult['errors'], 422, $requestId);
            }

            // 🛰️ Delegate to Cortex (D4 Authority)
            $response = $this->cortex->generateStructuredSeoMeta($structuredData, $options);

            if (!$response['success']) {
                $this->logError('seo_meta_generation_failed', $requestId, $response['error'] ?? 'Unknown error', $structuredData);
                return $this->errorResponse($response['error'] ?? 'SEO meta üretilemedi', 500, $requestId);
            }

            $durationMs = LogService::stopTimer($startTime);

            return [
                'success' => true,
                'data' => $response['data'],
                'provider' => $response['provider'] ?? 'cortex',
                'metadata' => array_merge($response['metadata'] ?? [], [
                    'duration_ms' => $durationMs,
                    'data_validation' => 'passed',
                    'request_id' => $requestId,
                ]),
            ];
        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            $this->logError('seo_meta_generation_exception', $requestId, $e->getMessage(), $structuredData, $e);

            return $this->errorResponse('SEO meta üretimi sırasında hata oluştu', 500, $requestId);
        }
    }

    /**
     * Validate for Title
     */
    protected function validateForTitle(array $data): array
    {
        $errors = [];

        if (empty($data['lokasyon'])) {
            $errors[] = 'lokasyon gerekli';
        } else {
            if (empty($data['lokasyon']['il'])) {
                $errors[] = 'lokasyon.il gerekli';
            }
            if (empty($data['lokasyon']['ilce'])) {
                $errors[] = 'lokasyon.ilce gerekli';
            }
        }

        if (empty($data['konut_tipi'])) {
            $errors[] = 'konut_tipi gerekli';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate for Summary/Description
     */
    protected function validateForSummary(array $data): array
    {
        $errors = [];

        if (empty($data['lokasyon'])) {
            $errors[] = 'lokasyon gerekli';
        } else {
            if (empty($data['lokasyon']['il'])) {
                $errors[] = 'lokasyon.il gerekli';
            }
        }

        if (empty($data['konut_tipi'])) {
            $errors[] = 'konut_tipi gerekli';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate for Description
     */
    protected function validateForDescription(array $data): array
    {
        return $this->validateForSummary($data);
    }

    /**
     * Build Title Prompt
     */
    protected function buildTitlePrompt(array $data): string
    {
        $lokasyon = $this->formatLocation($data['lokasyon'] ?? null);
        $konutTipi = $data['konut_tipi'] ?? '';
        $ozellikler = $this->extractTopFeatures($data);

        $prompt = <<<EOT
Sen bir emlak ve tatil kiralama sistemi için başlık üreten AI modülüsün.

⚠️ KRİTİK KURALLAR:
1. ASLA varsayım yapma
2. ASLA veri olmayan özelliği yazma
3. ASLA fiyat, kişi sayısı, mesafe, manzara, havuz, plaj bilgisi uydurma
4. ASLA kategori veya yayın tipi üretme/değiştirme
5. SADECE aşağıda verilen STRUCTURED DATA üzerinden konuş

--- YAPISAL VERİLER ---

Lokasyon:
{$lokasyon}

Konut Tipi: {$konutTipi}

Öne Çıkan Özellikler:
{$ozellikler}

--- TALİMAT ---

1 cümle başlık üret:
- Sıralama: {ilce/mahalle} + {konut_tipi} + (varsa) "Özel Havuzlu" + (varsa) "Deniz Manzaralı" + (varsa) "Müstakil"
- En fazla 110 karakter
- Abartı YOK
- SEO uyumlu anahtar kelimeler
- Sadece başlığı yaz, numaralama yapma

Başlık:
EOT;

        return $prompt;
    }

    /**
     * Build Summary Prompt
     */
    protected function buildSummaryPrompt(array $data): string
    {
        $lokasyon = $this->formatLocation($data['lokasyon'] ?? null);
        $konutTipi = $data['konut_tipi'] ?? '';
        $ozellikler = $this->extractTopFeatures($data);

        $prompt = <<<EOT
Sen bir emlak ve tatil kiralama sistemi için kısa özet üreten AI modülüsün.

⚠️ KRİTİK KURALLAR:
1. ASLA varsayım yapma
2. ASLA veri olmayan özelliği yazma
3. ASLA fiyat, kişi sayısı, mesafe, manzara, havuz, plaj bilgisi uydurma
4. ASLA kategori veya yayın tipi üretme/değiştirme
5. SADECE aşağıda verilen STRUCTURED DATA üzerinden konuş

--- YAPISAL VERİLER ---

Lokasyon:
{$lokasyon}

Konut Tipi: {$konutTipi}

Özellikler:
{$ozellikler}

--- TALİMAT ---

2-3 cümle kısa özet üret:
- En kritik özellikler
- Okunabilir ve sade
- SEO uyumlu
- Abartı YOK

Özet:
EOT;

        return $prompt;
    }

    /**
     * Build Description Prompt
     */
    protected function buildDescriptionPrompt(array $data): string
    {
        $lokasyon = $this->formatLocation($data['lokasyon'] ?? null);
        $konutTipi = $data['konut_tipi'] ?? '';
        $kapasite = $this->formatCapacity($data);
        $havuzDeniz = $this->formatPoolSea($data);
        $konfor = $this->formatComfort($data);
        $bahce = $this->formatGarden($data);
        $kurallar = $this->formatRules($data);
        $mesafe = $this->formatDistances($data);

        $prompt = <<<EOT
Sen bir emlak ve tatil kiralama sistemi için detaylı açıklama üreten AI modülüsün.

⚠️ KRİTİK KURALLAR:
1. ASLA varsayım yapma
2. ASLA veri olmayan özelliği yazma
3. ASLA fiyat, kişi sayısı, mesafe, manzara, havuz, plaj bilgisi uydurma
4. ASLA kategori veya yayın tipi üretme/değiştirme
5. SADECE aşağıda verilen STRUCTURED DATA üzerinden konuş

--- YAPISAL VERİLER ---

Lokasyon:
{$lokasyon}

Konut Tipi: {$konutTipi}

Kapasite:
{$kapasite}

Havuz & Deniz:
{$havuzDeniz}

Konfor & Lüks:
{$konfor}

Bahçe & Dış Alan:
{$bahce}

Kurallar:
{$kurallar}

Mesafe Cetveli:
{$mesafe}

--- TALİMAT ---

Aşağıdaki sırayı KORU (veri yoksa bölümü tamamen atla):

1. Genel Tanım
2. Konum & Çevre
3. Kapasite & Plan
4. Havuz/Deniz (SADECE veri varsa)
5. Konfor & Donanım
6. Kurallar (SADECE veri varsa)
7. Mesafeler (SADECE veri varsa)

YASAKLI İFADELER:
- "Eşsiz", "benzersiz", "rakipsiz"
- "Hayalinizdeki"
- "Cennetten bir köşe"
- Gerçek olmayan vaatler

Ton: Net, güven veren, satış değil bilgilendirme odaklı

Açıklama:
EOT;

        return $prompt;
    }

    /**
     * Build SEO Meta Prompt
     */
    protected function buildSeoMetaPrompt(array $data): string
    {
        $lokasyon = $this->formatLocation($data['lokasyon'] ?? null);
        $konutTipi = $data['konut_tipi'] ?? '';
        $ozellikler = $this->extractTopFeatures($data);

        $prompt = <<<EOT
Sen bir emlak ve tatil kiralama sistemi için SEO meta üreten AI modülüsün.

⚠️ KRİTİK KURALLAR:
1. ASLA varsayım yapma
2. ASLA veri olmayan özelliği yazma
3. SADECE aşağıda verilen STRUCTURED DATA üzerinden konuş

--- YAPISAL VERİLER ---

Lokasyon:
{$lokasyon}

Konut Tipi: {$konutTipi}

Özellikler:
{$ozellikler}

--- TALİMAT ---

JSON formatında döndür:
{
  "meta_title": "60 karakter max, SEO uyumlu",
  "meta_description": "155 karakter max, SEO uyumlu"
}

Sadece JSON döndür, başka açıklama yapma.

JSON:
EOT;

        return $prompt;
    }

    /**
     * Format Location
     */
    protected function formatLocation(?array $lokasyon): string
    {
        if (!$lokasyon) {
            return 'Lokasyon bilgisi yok';
        }

        $parts = [];
        if (!empty($lokasyon['il'])) $parts[] = "İl: {$lokasyon['il']}";
        if (!empty($lokasyon['ilce'])) $parts[] = "İlçe: {$lokasyon['ilce']}";
        if (!empty($lokasyon['mahalle'])) $parts[] = "Mahalle: {$lokasyon['mahalle']}";

        return !empty($parts) ? implode("\n", $parts) : 'Lokasyon bilgisi yok';
    }

    /**
     * Extract Top Features
     */
    protected function extractTopFeatures(array $data): string
    {
        $features = [];

        $havuzDeniz = $data['havuz_deniz'] ?? null;
        if ($havuzDeniz) {
            if (!empty($havuzDeniz['ozel_havuz']) && $havuzDeniz['ozel_havuz']) {
                $features[] = 'Özel Havuz';
            }
            if (!empty($havuzDeniz['deniz_manzarasi']) && $havuzDeniz['deniz_manzarasi'] !== 'yok') {
                $features[] = 'Deniz Manzaralı';
            }
            if (!empty($havuzDeniz['denize_sifir']) && $havuzDeniz['denize_sifir']) {
                $features[] = 'Denize Sıfır';
            }
        }

        $konfor = $data['konfor'] ?? null;
        if ($konfor) {
            if (!empty($konfor['jakuzi']) && $konfor['jakuzi']) $features[] = 'Jakuzi';
            if (!empty($konfor['somine']) && $konfor['somine']) $features[] = 'Şömine';
        }

        $bahce = $data['bahce'] ?? null;
        if ($bahce) {
            if (!empty($bahce['bahce']) && $bahce['bahce']) $features[] = 'Bahçe';
        }

        $kapasite = $data['kapasite'] ?? null;
        if ($kapasite && !empty($kapasite['mustakil']) && $kapasite['mustakil']) {
            $features[] = 'Müstakil';
        }

        return !empty($features) ? implode(', ', array_slice($features, 0, 3)) : 'Özel özellik belirtilmemiş';
    }

    /**
     * Format Capacity
     */
    protected function formatCapacity(array $data): string
    {
        $kapasite = $data['kapasite'] ?? null;
        if (!$kapasite) {
            return 'Kapasite bilgisi yok';
        }

        $parts = [];

        if (isset($kapasite['kisi_kapasitesi']) && $kapasite['kisi_kapasitesi'] !== null) {
            $parts[] = "Kişi Kapasitesi: {$kapasite['kisi_kapasitesi']}";
        }
        if (isset($kapasite['yatak_odasi']) && $kapasite['yatak_odasi'] !== null) {
            $parts[] = "Yatak Odası: {$kapasite['yatak_odasi']}";
        }
        if (isset($kapasite['banyo']) && $kapasite['banyo'] !== null) {
            $parts[] = "Banyo: {$kapasite['banyo']}";
        }
        if (isset($kapasite['mustakil'])) {
            $parts[] = "Müstakil: " . ($kapasite['mustakil'] ? 'Evet' : 'Hayır');
        }

        return !empty($parts) ? implode("\n", $parts) : 'Kapasite bilgisi yok';
    }

    /**
     * Format Pool & Sea
     */
    protected function formatPoolSea(array $data): string
    {
        $havuzDeniz = $data['havuz_deniz'] ?? null;
        if (!$havuzDeniz) {
            return 'Havuz/deniz bilgisi yok';
        }

        $parts = [];

        if (isset($havuzDeniz['ozel_havuz']) && $havuzDeniz['ozel_havuz']) {
            $parts[] = 'Özel Havuz: Var';
            if (isset($havuzDeniz['isitmali_havuz']) && $havuzDeniz['isitmali_havuz']) {
                $parts[] = '- Isıtmalı';
            }
            if (isset($havuzDeniz['havuz_korunakli']) && $havuzDeniz['havuz_korunakli']) {
                $parts[] = '- Korunaklı';
            }
        }

        if (!empty($havuzDeniz['deniz_manzarasi']) && $havuzDeniz['deniz_manzarasi'] !== 'yok') {
            $parts[] = "Deniz Manzarası: {$havuzDeniz['deniz_manzarasi']}";
        }
        if (isset($havuzDeniz['denize_sifir']) && $havuzDeniz['denize_sifir']) {
            $parts[] = 'Denize Sıfır: Evet';
        }
        if (isset($havuzDeniz['ozel_plaj']) && $havuzDeniz['ozel_plaj']) {
            $parts[] = 'Özel Plaj: Var';
        }
        if (isset($havuzDeniz['denize_mesafe']) && $havuzDeniz['denize_mesafe'] !== null) {
            $parts[] = "Denize Mesafe: {$havuzDeniz['denize_mesafe']} metre";
        }

        return !empty($parts) ? implode("\n", $parts) : 'Havuz/deniz bilgisi yok';
    }

    /**
     * Format Comfort
     */
    protected function formatComfort(array $data): string
    {
        $konfor = $data['konfor'] ?? null;
        if (!$konfor) {
            return 'Konfor özelliği belirtilmemiş';
        }

        $parts = [];

        if (isset($konfor['jakuzi']) && $konfor['jakuzi']) $parts[] = 'Jakuzi';
        if (isset($konfor['sauna']) && $konfor['sauna']) $parts[] = 'Sauna';
        if (isset($konfor['somine']) && $konfor['somine']) $parts[] = 'Şömine';
        if (isset($konfor['klima']) && $konfor['klima']) $parts[] = 'Klima/VRF';
        if (isset($konfor['akilli_ev']) && $konfor['akilli_ev']) $parts[] = 'Akıllı Ev';

        return !empty($parts) ? implode(', ', $parts) : 'Konfor özelliği belirtilmemiş';
    }

    /**
     * Format Garden
     */
    protected function formatGarden(array $data): string
    {
        $bahce = $data['bahce'] ?? null;
        if (!$bahce) {
            return 'Bahçe/dış alan bilgisi yok';
        }

        $parts = [];

        if (isset($bahce['bahce']) && $bahce['bahce']) $parts[] = 'Bahçe';
        if (isset($bahce['veranda']) && $bahce['veranda']) $parts[] = 'Veranda';
        if (isset($bahce['barbeku']) && $bahce['barbeku']) $parts[] = 'Barbekü';
        if (isset($bahce['sezlong']) && $bahce['sezlong']) $parts[] = 'Şezlong';

        return !empty($parts) ? implode(', ', $parts) : 'Bahçe/dış alan bilgisi yok';
    }

    /**
     * Format Rules
     */
    protected function formatRules(array $data): string
    {
        $kurallar = $data['kurallar'] ?? null;
        if (!$kurallar) {
            return 'Kural belirtilmemiş';
        }

        $parts = [];

        if (isset($kurallar['evcil_hayvan'])) {
            $parts[] = "Evcil Hayvan: " . ($kurallar['evcil_hayvan'] ? 'İzinli' : 'Yasak');
        }
        if (isset($kurallar['sigara'])) {
            $parts[] = "Sigara: " . ($kurallar['sigara'] ? 'İzinli' : 'Yasak');
        }
        if (isset($kurallar['parti'])) {
            $parts[] = "Parti: " . ($kurallar['parti'] ? 'İzinli' : 'Yasak');
        }
        if (!empty($kurallar['giris_saati'])) {
            $parts[] = "Giriş Saati: {$kurallar['giris_saati']}";
        }
        if (!empty($kurallar['cikis_saati'])) {
            $parts[] = "Çıkış Saati: {$kurallar['cikis_saati']}";
        }

        return !empty($parts) ? implode("\n", $parts) : 'Kural belirtilmemiş';
    }

    /**
     * Format Distances
     */
    protected function formatDistances(array $data): string
    {
        $mesafe = $data['mesafe'] ?? null;
        if (!$mesafe) {
            return 'Mesafe bilgisi yok';
        }

        $parts = [];

        if (isset($mesafe['havalimani']) && $mesafe['havalimani'] !== null) {
            $parts[] = "Havalimanı: {$mesafe['havalimani']} km";
        }
        if (isset($mesafe['market']) && $mesafe['market'] !== null) {
            $parts[] = "Market: {$mesafe['market']} km";
        }
        if (isset($mesafe['hastane']) && $mesafe['hastane'] !== null) {
            $parts[] = "Hastane: {$mesafe['hastane']} km";
        }
        if (isset($mesafe['merkez']) && $mesafe['merkez'] !== null) {
            $parts[] = "Merkez: {$mesafe['merkez']} km";
        }
        if (isset($mesafe['plaj']) && $mesafe['plaj'] !== null) {
            $parts[] = "Plaj: {$mesafe['plaj']} km";
        }

        return !empty($parts) ? implode("\n", $parts) : 'Mesafe bilgisi yok';
    }

    /**
     * Generate Content via Provider
     */
    protected function generateContent(string $prompt, string $provider, array $options = []): array
    {
        // 🛡️ Phase 23: Budget Guard
        $budget = $this->costGuard->checkBudget($provider);
        if (!$budget['allowed']) {
            return [
                'success' => false,
                'error' => 'AI bütçe sınırı aşıldı: ' . $budget['reason'],
            ];
        }

        try {
            if ($provider === 'ollama') {
                $response = $this->ollama->generateCompletion($prompt, $options['max_tokens'] ?? 200);

                if (isset($response['error']) && $response['error'] === true) {
                    $errorMsg = $response['message'] ?? 'Ollama servisi yanıt vermedi';
                    throw new \Exception("Ollama hatası: {$errorMsg}");
                }

                return [
                    'success' => true,
                    'content' => $response['response'] ?? '',
                ];
            } else {
                $messages = [
                    ['role' => 'system', 'content' => 'Sen data-driven emlak içerik üretim modülüsün. SADECE verilen structured data üzerinden konuş. ASLA varsayım yapma.'],
                    ['role' => 'user', 'content' => $prompt]
                ];
                $response = $this->openai->chat($messages, $options['model'] ?? 'gpt-3.5-turbo', $options['temperature'] ?? 0.7);

                return [
                    'success' => true,
                    'content' => $response['content'] ?? '',
                ];
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            if (str_contains(strtolower($errorMessage), 'bağlanılamadı') ||
                str_contains(strtolower($errorMessage), 'connection')) {
                $errorMessage = 'AI servisine bağlanılamadı. Lütfen servis durumunu kontrol edin.';
            } elseif (str_contains(strtolower($errorMessage), 'timeout')) {
                $errorMessage = 'AI servisi yanıt vermedi (timeout). Lütfen tekrar deneyin.';
            }

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Parse Title Response
     */
    protected function parseTitleResponse(string $rawContent): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $rawContent)));
        $titles = [];

        foreach ($lines as $line) {
            $clean = preg_replace('/^[\d\.\-\*]+\s*/', '', $line);
            $clean = trim($clean);
            if (strlen($clean) > 10 && strlen($clean) <= 110) {
                $titles[] = $clean;
            }
        }

        return [
            'primary' => $titles[0] ?? '',
            'alternatives' => array_slice($titles, 1, 4),
        ];
    }

    /**
     * Parse SEO Meta Response
     */
    protected function parseSeoMetaResponse(string $rawContent): array
    {
        $jsonMatch = [];
        if (preg_match('/\{[^}]+\}/s', $rawContent, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[0], true);
            if (is_array($decoded)) {
                return [
                    'meta_title' => $decoded['meta_title'] ?? '',
                    'meta_description' => $decoded['meta_description'] ?? '',
                ];
            }
        }

        return [
            'meta_title' => '',
            'meta_description' => '',
        ];
    }

    /**
     * Get Provider
     */
    protected function getProvider(array $options): string
    {
        return $options['provider'] ?? config('ai.default_provider', 'ollama');
    }

    /**
     * Error Response
     */
    protected function errorResponse($errors, int $code, string $requestId): array
    {
        $errorMessage = is_array($errors) ? implode(', ', $errors) : $errors;

        return [
            'success' => false,
            'error' => $errorMessage,
            'metadata' => [
                'request_id' => $requestId,
                'data_validation' => 'failed',
            ],
        ];
    }

    /**
     * Log Success
     */
    protected function logSuccess(string $action, string $requestId, int $durationMs, string $provider, array $data, array $guardActions = []): void
    {
        $dataSummary = $this->getDataSummary($data);

        LogService::ai("data_driven_{$action}", 'DataDrivenAIContent', [
            'request_id' => $requestId,
            'duration_ms' => $durationMs,
            'provider' => $provider,
            'data_keys_present' => array_keys($dataSummary),
            'data_summary' => $dataSummary,
            'guard_actions_count' => count($guardActions),
        ]);
    }

    /**
     * Log Error
     */
    protected function logError(string $action, string $requestId, string $error, array $data, ?\Exception $exception = null): void
    {
        $dataSummary = $this->getDataSummary($data);

        LogService::error("Data-driven {$action} failed", [
            'request_id' => $requestId,
            'error' => $error,
            'data_keys_present' => array_keys($dataSummary),
            'data_summary' => $dataSummary,
        ], $exception);
    }

    /**
     * Get Data Summary (PII masked)
     */
    protected function getDataSummary(array $data): array
    {
        $summary = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $summary[$key] = count($value) . ' items';
            } elseif (is_string($value)) {
                $summary[$key] = strlen($value) . ' chars';
            } elseif (is_bool($value)) {
                $summary[$key] = $value ? 'true' : 'false';
            } elseif (is_numeric($value)) {
                $summary[$key] = 'numeric';
            } else {
                $summary[$key] = gettype($value);
            }
        }

        return $summary;
    }
}

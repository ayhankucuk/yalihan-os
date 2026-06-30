<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CortexKnowledgeService
 *
 * Context7 Standard: C7-CORTEX-KNOWLEDGE-SERVICE-2025-11-30
 *
 * AnythingLLM ile RAG (Retrieval-Augmented Generation) entegrasyonu sağlar.
 * Özellikle imar plan notları gibi dokümanlardan bilgi çekmek için kullanılır.
 */
class CortexKnowledgeService
{
    private string $anythingLlmUrl;
    private ?string $anythingLlmKey = null;
    private string $anythingLlmWorkspace;
    private int $timeout;

    public function __construct()
    {
        $this->anythingLlmUrl = (string) config('ai.anything_llm.url', 'http://localhost:3001');
        $this->anythingLlmKey = (string) config('ai.anything_llm.api_key');
        $this->anythingLlmWorkspace = (string) config('ai.anything_llm.workspace_id', 'yalihan-hukuk');

        // Timeout'u config'den al, varsayılan 60 saniye
        $this->timeout = (int) config('ai.anything_llm.timeout', 60);

        // Settings tablosundan override etme (eğer varsa)
        try {
            if (class_exists(\App\Models\Setting::class)) {
                $settings = \App\Models\Setting::query()
                    ->whereIn('key', ['ai_anythingllm_url', 'ai_anythingllm_api_key', 'ai_anythingllm_workspace', 'ai_anythingllm_timeout'])
                    ->pluck('value', 'key');

                $this->anythingLlmUrl = (string) ($settings['ai_anythingllm_url'] ?? $this->anythingLlmUrl);
                $this->anythingLlmKey = (string) ($settings['ai_anythingllm_api_key'] ?? $this->anythingLlmKey);
                $this->anythingLlmWorkspace = (string) ($settings['ai_anythingllm_workspace'] ?? $this->anythingLlmWorkspace);
                $this->timeout = (int) ($settings['ai_anythingllm_timeout'] ?? $this->timeout);
            }
        } catch (\Throwable $e) {
            Log::notice('CortexKnowledgeService settings override skipped', ['error' => $e->getMessage()]);
        }

        if (empty($this->anythingLlmKey)) {
            Log::error('ANYTHINGLLM_KEY environment variable is not set.');
            // throw new \RuntimeException('AnythingLLM API Key is not configured.');
        }
    }

    /**
     * AnythingLLM'e imar plan notları sorgusu gönderir ve inşaat haklarını döndürür.
     *
     * @param array $data [ilce, mahalle, ada, parsel, m2]
     * @param int $maxRetries Maksimum retry sayısı (varsayılan: 2)
     * @return array [kaks, taks, gabari, cekme_mesafeleri, toplam_insaat_alani, kaynak_dokuman]
     */
    public function queryConstructionRights(array $data, int $maxRetries = 2): array
    {
        if (empty($this->anythingLlmKey)) {
            Log::error('CortexKnowledgeService: ANYTHINGLLM_KEY eksik, sorgu yapılamadı.');
            return ['error' => 'Knowledge Base\'e erişilemedi, lütfen manuel kontrol edin. (API Key eksik)'];
        }

        // Cache key oluştur
        $cacheKey = $this->generateCacheKey($data);

        // Cache kontrolü - Eğer cache'de varsa direkt dön
        if (Cache::has($cacheKey)) {
            $cachedResult = Cache::get($cacheKey);
            Log::info('Cortex Cache HIT', [
                'key' => $cacheKey,
                'ada_no' => $data['ada_no'] ?? null,
                'parsel_no' => $data['parsel_no'] ?? null,
            ]);
            return $cachedResult;
        }

        // Cache MISS - API sorgusu yapılacak
        Log::info('Cortex Cache MISS', [
            'key' => $cacheKey,
            'ada_no' => $data['ada_no'] ?? null,
            'parsel_no' => $data['parsel_no'] ?? null,
        ]);

        $systemPrompt = "Sen Yalıhan Emlak'ın Kıdemli Şehir Plancısısın. Verilen lokasyon ve parsel bilgilerini, veritabanındaki 'İmar Plan Notları' dokümanlarıyla karşılaştır. Bu arsa için KAKS (Emsal), TAKS, Gabari (Yükseklik) ve Çekme Mesafelerini tespit et. Toplam inşaat alanını hesapla. Kaynak dokümanı belirt.";

        $userPrompt = "Lokasyon Bilgileri:\n";
        $userPrompt .= "İlçe: " . ($data['ilce'] ?? 'Belirtilmemiş') . "\n";
        $userPrompt .= "Mahalle: " . ($data['mahalle'] ?? 'Belirtilmemiş') . "\n";
        $userPrompt .= "Ada No: " . ($data['ada_no'] ?? 'Belirtilmemiş') . "\n";
        $userPrompt .= "Parsel No: " . ($data['parsel_no'] ?? 'Belirtilmemiş') . "\n";
        $userPrompt .= "Arsa Alanı (m²): " . ($data['alan_m2'] ?? 'Belirtilmemiş') . "\n";
        $userPrompt .= "Bu bilgilere göre imar plan notlarını analiz et ve KAKS, TAKS, Gabari, Çekme Mesafeleri ve Toplam İnşaat Alanını hesapla. Kaynak dokümanı belirt.";

        try {
            // Laravel'in yerleşik retry mekanizması: 3 deneme, 1 saniye bekleme
            // AI servisleri yavaş olabilir, bu yüzden daha sabırlıyız
            // 4xx hatalarında retry yapmaz (client hatası)
            $response = Http::retry(3, 1000, function ($exception, $request) {
                // ConnectionException (ağ hatası) statusunda retry yap
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                // RequestException statusunda status code'a bak
                if ($exception instanceof RequestException) {
                    $statusCode = $exception->response?->status(); // context7-ignore
                    // 5xx hatalarında retry yap, 4xx hatalarında yapma
                    return $statusCode >= 500;
                }

                // Diğer exception'larda retry yap
                return true;
            })
                ->timeout(120) // RAG işlemi uzun sürer, 2 dakika timeout
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->anythingLlmKey,
                    'x-anythingllm-workspace-id' => $this->anythingLlmWorkspace, // Workspace ID'yi header olarak gönder
                ])
                ->post(rtrim($this->anythingLlmUrl, '/') . '/openai/chat/completions', [
                    'model' => 'gpt-3.5-turbo', // AnythingLLM OpenAI uyumlu model kullanır
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 1024,
                ])
                ->throw(); // Hata statusunda exception fırlat

            // Başarılı
            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? 'Analiz yapılamadı.';

            // Basit bir parsing ile anahtar bilgileri çekmeye çalış
            $kaks = $this->extractValue($content, 'KAKS');
            $taks = $this->extractValue($content, 'TAKS');
            $gabari = $this->extractValue($content, 'Gabari');
            $cekmeMesafeleri = $this->extractValue($content, 'Çekme Mesafeleri');
            $toplamInsaatAlani = $this->extractValue($content, 'Toplam İnşaat Alanı');
            $kaynakDokuman = $this->extractValue($content, 'Kaynak Doküman');

            $result = [
                'success' => true,
                'data' => [
                    'kaks' => $kaks,
                    'taks' => $taks,
                    'gabari' => $gabari,
                    'cekme_mesafeleri' => $cekmeMesafeleri,
                    'toplam_insaat_alani' => $toplamInsaatAlani,
                    'kaynak_dokuman' => $kaynakDokuman,
                    'raw_response' => $content, // Debug için ham cevabı da sakla
                ],
                'source' => 'AnythingLLM - ' . $this->anythingLlmWorkspace,
            ];

            // Başarılı sonucu cache'le (24 saat)
            Cache::put($cacheKey, $result, now()->addHours(24));

            Log::info('CortexKnowledgeService: İmar analizi başarılı ve cache\'lendi', [
                'ada_no' => $data['ada_no'] ?? null,
                'parsel_no' => $data['parsel_no'] ?? null,
                'cache_key' => $cacheKey,
            ]);

            Log::info('Cortex Cache MISS - Stored', [
                'key' => $cacheKey,
                'ada_no' => $data['ada_no'] ?? null,
                'parsel_no' => $data['parsel_no'] ?? null,
            ]);

            return $result;
        } catch (RequestException $e) {
            // HTTP hatası (4xx, 5xx)
            $statusCode = $e->response?->status() ?? 0; // context7-ignore

            if ($statusCode >= 400 && $statusCode < 500) {
                // Client hatası (4xx) - retry yapılmadı (doğru)
                Log::error('CortexKnowledgeService: Client hatası, retry yapılmadı', [
                    'status' => $statusCode, // context7-ignore
                    'response' => $e->response?->body(),
                    'data' => $data,
                ]);
                return ['error' => 'Knowledge Base\'den yanıt alınamadı: ' . $statusCode];
            } else {
                // Server hatası (5xx) - retry yapıldı ama başarısız
                Log::error('CortexKnowledgeService: Server hatası, tüm retry\'lar tükendi', [
                    'status' => $statusCode, // context7-ignore
                    'response' => $e->response?->body(),
                    'data' => $data,
                ]);
                return ['error' => 'Knowledge Base\'den yanıt alınamadı: ' . $statusCode];
            }
        } catch (ConnectionException $e) {
            // Ağ bağlantı hatası - retry yapıldı ama başarısız
            Log::error('CortexKnowledgeService: AI Servisine Bağlanılamadı (Offline)', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return ['error' => 'AI Servisine Bağlanılamadı (Offline). Lütfen servis statusunu kontrol edin.'];
        } catch (\Exception $e) {
            // Diğer exception'lar
            Log::error('CortexKnowledgeService: AI İşlem Hatası', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'data' => $data,
            ]);
            return ['error' => 'AI İşlem Hatası: ' . $e->getMessage()];
        }
    }

    /**
     * Metinden belirli bir değeri çıkarmak için yardımcı fonksiyon.
     */
    private function extractValue(string $text, string $key): ?string
    {
        if (preg_match("/{$key}:\s*(.*?)(?:\n|$)/i", $text, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Cache key oluştur
     *
     * Format: cortex:construction:{ilce}:{mahalle}:{ada}:{parsel}
     * Boşluklar temizlenir, lowercase yapılır.
     *
     * @param array $data
     * @return string
     */
    private function generateCacheKey(array $data): string
    {
        $ilce = $this->normalizeCacheKeyPart($data['ilce'] ?? 'unknown');
        $mahalle = $this->normalizeCacheKeyPart($data['mahalle'] ?? 'unknown');
        $adaNo = $this->normalizeCacheKeyPart($data['ada_no'] ?? 'unknown');
        $parselNo = $this->normalizeCacheKeyPart($data['parsel_no'] ?? 'unknown');

        return "cortex:construction:{$ilce}:{$mahalle}:{$adaNo}:{$parselNo}";
    }

    /**
     * Cache key parçasını normalize et
     *
     * - Boşlukları temizle
     * - Lowercase yap
     * - Özel karakterleri temizle
     *
     * @param string $part
     * @return string
     */
    private function normalizeCacheKeyPart(string $part): string
    {
        // Boşlukları temizle, lowercase yap
        $normalized = strtolower(trim($part));

        // Özel karakterleri ve boşlukları alt çizgi ile değiştir
        $normalized = preg_replace('/[^a-z0-9]/', '_', $normalized);

        // Birden fazla alt çizgiyi tek alt çizgiye çevir
        $normalized = preg_replace('/_+/', '_', $normalized);

        // Başta ve sonda alt çizgi varsa temizle
        $normalized = trim($normalized, '_');

        // Eğer boşsa 'unknown' dön
        return $normalized ?: 'unknown';
    }
}

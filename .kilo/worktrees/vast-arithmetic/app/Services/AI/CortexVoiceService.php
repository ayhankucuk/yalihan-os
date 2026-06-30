<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\Talep;
use App\Services\AIService;
use App\Services\AI\AiTelemetryService;
use App\Services\AI\VoiceSearchService;
use App\Services\Logging\LogService;
use App\Enums\AktiflikDurumu;
use App\Enums\TalepDurumu;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * CortexVoiceService — Voice-to-CRM Pipeline
 *
 * #19 YalihanCortex God Object dekompozisyonu.
 * Sesli arama ve sesli komut ile CRM kaydı oluşturma pipeline'ını
 * YalihanCortex'ten buraya taşır.
 *
 * Sorumluluk:
 *  - processVoiceSearch(): Ses → Metin → Arama (VoiceSearchService delegate)
 *  - createDraftFromText(): Doğal dil metni → Kisi + Talep draft (NLP pipeline)
 *
 * Context7: C7-VOICE-TO-CRM-2025-11-27
 * SAB: Repository write authority korunur (Kisi/Talep::create Model üzerinden)
 */
class CortexVoiceService
{
    public function __construct(
        private readonly AIService $aiService,
        private readonly VoiceSearchService $voiceSearch,
        private readonly AiTelemetryService $telemetry,
    ) {}

    // ──────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────────────────────────

    /**
     * Voice Search — Sesli arama işleme
     *
     * @CortexDecision Voice → Text → Search
     *
     * @param string $audioFile Base64 audio veya dosya yolu
     * @param array  $options   Voice search seçenekleri
     * @return array            Arama sonuçları
     */
    public function processVoiceSearch(string $audioFile, array $options = []): array
    {
        $startTime = LogService::startTimer('cortex_voice_search');

        try {
            LogService::ai(
                'cortex_voice_search_started',
                'CortexVoiceService',
                [
                    'provider' => $options['provider'] ?? 'whisper',
                    'language' => $options['language'] ?? 'tr-TR',
                ]
            );

            $result = $this->voiceSearch->processVoiceSearch($audioFile, $options);

            $durationMs = LogService::stopTimer($startTime);

            $this->logDecision('voice_search', [
                'transcription' => $result['transcription']['text'] ?? '',
                'intent'        => $result['intent'] ?? [],
                'results_count' => count($result['results'] ?? []),
            ], $durationMs, true);

            LogService::ai(
                'cortex_voice_search_completed',
                'CortexVoiceService',
                [
                    'transcription' => $result['transcription']['text'] ?? '',
                    'results_count' => count($result['results'] ?? []),
                    'duration_ms'   => $durationMs,
                ]
            );

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            $this->logDecision('voice_search', [
                'error' => $e->getMessage(),
            ], $durationMs, false);

            LogService::error(
                'CortexVoiceService: voice search failed',
                ['error' => $e->getMessage()],
                $e,
                LogService::CHANNEL_AI
            );

            throw $e;
        }
    }

    /**
     * Sesli komut ile hızlı kayıt oluşturma
     *
     * @CortexDecision
     * Doğal dilden gelen metni JSON'a çevirip Kisi + Talep draft kayıtları oluşturur.
     *
     * Context7: C7-VOICE-TO-CRM-2025-11-27
     * Kullanım: Telegram/WhatsApp sesli mesajdan gelen metin → Kisi + Talep draft
     *
     * @param string $rawText     Doğal dil metni
     * @param int    $danismanId  Danışman ID
     * @param array  $options     Ek seçenekler
     * @return array
     */
    public function createDraftFromText(string $rawText, int $danismanId, array $options = []): array
    {
        $startTime = LogService::startTimer('cortex_voice_to_crm');

        try {
            LogService::ai(
                'cortex_voice_to_crm_started',
                'CortexVoiceService',
                [
                    'danisman_id'  => $danismanId,
                    'text_length'  => strlen($rawText),
                    'text_preview' => substr($rawText, 0, 100),
                ]
            );

            // 1. NLP ile metni JSON'a çevir
            $structuredData = $this->extractStructuredDataFromText($rawText);

            // 2. JSON'u validate et
            $validationResult = $this->validateStructuredData($structuredData);
            if (! $validationResult['valid']) {
                throw new InvalidArgumentException(
                    'Structured data validation failed: ' . $validationResult['error']
                );
            }

            // 3. Kisi oluştur veya bul
            $kisi = $this->createOrFindKisi($structuredData['kisi'], $danismanId);

            // 4. Talep draft oluştur
            $talep = $this->createDraftTalep($structuredData['talep'], $kisi->id, $danismanId);

            // 5. Performans metrikleri
            $durationMs = LogService::stopTimer($startTime);

            $result = [
                'success'  => true,
                'kisi_id'  => $kisi->id,
                'talep_id' => $talep->id,
                'kisi'     => [
                    'id'      => $kisi->id,
                    'ad'      => $kisi->ad,
                    'soyad'   => $kisi->soyad,
                    'telefon' => $kisi->telefon,
                    'email'   => $kisi->email,
                ],
                'talep' => [
                    'id'            => $talep->id,
                    'baslik'        => $talep->baslik ?? null,
                    'talep_durumu'  => $talep->talep_durumu, // Context7: Direct column reference
                    'tip'           => $talep->tip ?? null,
                ],
                'metadata' => [
                    'processed_at'     => now()->toISOString(),
                    'algorithm'        => 'CortexVoiceService v1.0',
                    'duration_ms'      => $durationMs,
                    'confidence_score' => $structuredData['confidence_score'] ?? 0,
                ],
            ];

            $this->logDecision('voice_to_crm', [
                'danisman_id' => $danismanId,
                'kisi_id'     => $kisi->id,
                'talep_id'    => $talep->id,
                'text_length' => strlen($rawText),
            ], $durationMs, true);

            LogService::ai(
                'cortex_voice_to_crm_completed',
                'CortexVoiceService',
                [
                    'danisman_id' => $danismanId,
                    'kisi_id'     => $kisi->id,
                    'talep_id'    => $talep->id,
                    'duration_ms' => $durationMs,
                ]
            );

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            $this->logDecision('voice_to_crm', [
                'danisman_id' => $danismanId,
                'error'       => $e->getMessage(),
                'text_length' => strlen($rawText),
            ], $durationMs, false);

            LogService::error(
                'CortexVoiceService: voice-to-CRM failed',
                [
                    'danisman_id' => $danismanId,
                    'text_length' => strlen($rawText),
                    'error'       => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            return [
                'success'  => false,
                'error'    => $e->getMessage(),
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'algorithm'    => 'CortexVoiceService v1.0',
                    'duration_ms'  => $durationMs,
                ],
            ];
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // NLP PIPELINE — Private helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * NLP ile metni yapılandırılmış JSON'a çevir
     */
    private function extractStructuredDataFromText(string $rawText): array
    {
        $prompt = $this->buildNLPParsePrompt($rawText);

        try {
            $aiResponse = $this->aiService->generate($prompt, [
                'temperature' => 0.3, // Düşük temperature = daha tutarlı çıktı
                'max_tokens'  => 1000,
            ]);

            return $this->parseAIResponseToJSON($aiResponse);
        } catch (Exception $e) {
            LogService::warning('AI NLP parsing failed, using fallback', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackTextParsing($rawText);
        }
    }

    /**
     * NLP prompt oluştur
     */
    private function buildNLPParsePrompt(string $rawText): string
    {
        return <<<PROMPT
Sen bir emlak CRM sistemi için doğal dil işleme (NLP) uzmanısın. Aşağıdaki Türkçe metni analiz edip JSON formatına çevir.

**Kurallar:**
1. Kişi bilgilerini (ad, soyad, telefon, email) çıkar
2. Talep bilgilerini (tip: Satılık/Kiralık, fiyat aralığı, lokasyon, kategori) çıkar
3. Emin olmadığın alanlara null yaz
4. Sadece JSON döndür, açıklama ekleme

**Beklenen JSON formatı:**
{
  "kisi": {
    "ad": "string|null",
    "soyad": "string|null",
    "telefon": "string|null",
    "email": "string|null"
  },
  "talep": {
    "tip": "Satılık|Kiralık",
    "baslik": "string",
    "min_fiyat": "number|null",
    "max_fiyat": "number|null",
    "il_adi": "string|null",
    "ilce_adi": "string|null",
    "mahalle_adi": "string|null",
    "kategori": "string|null",
    "aciklama": "string"
  },
  "confidence_score": "number (0-100)"
}

**İşlenecek metin:**
{$rawText}

**Hemen JSON döndür:**
PROMPT;
    }

    /**
     * AI yanıtını JSON'a parse et
     */
    private function parseAIResponseToJSON(mixed $aiResponse): array
    {
        if (is_string($aiResponse)) {
            if (preg_match('/```json\s*(\{.*?\})\s*```/s', $aiResponse, $matches)) {
                $jsonString = $matches[1];
            } elseif (preg_match('/(\{.*\})/s', $aiResponse, $matches)) {
                $jsonString = $matches[1];
            } else {
                $jsonString = $aiResponse;
            }

            $decoded = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($aiResponse)) {
            return $aiResponse;
        }

        throw new RuntimeException('AI response could not be parsed to JSON');
    }

    /**
     * Fallback: Basit regex-based parsing
     */
    private function fallbackTextParsing(string $rawText): array
    {
        $result = [
            'kisi' => [
                'ad'      => null,
                'soyad'   => null,
                'telefon' => null,
                'email'   => null,
            ],
            'talep' => [
                'tip'          => 'Satılık',
                'baslik'       => 'Yeni Talep',
                'min_fiyat'    => null,
                'max_fiyat'    => null,
                'il_adi'       => null,
                'ilce_adi'     => null,
                'mahalle_adi'  => null,
                'kategori'     => null,
                'aciklama'     => $rawText,
            ],
            'confidence_score' => 30, // Düşük confidence (fallback)
        ];

        // İsim soyisim (büyük harfle başlayan kelimeler)
        if (preg_match('/([A-ZÇĞİÖŞÜ][a-zçğıöşü]+)\s+([A-ZÇĞİÖŞÜ][a-zçğıöşü]+)/', $rawText, $matches)) {
            $result['kisi']['ad']    = $matches[1];
            $result['kisi']['soyad'] = $matches[2];
        }

        // Telefon (0 ile başlayan 11 haneli)
        if (preg_match('/(0[0-9]{10})/', $rawText, $matches)) {
            $result['kisi']['telefon'] = $matches[1];
        }

        // Fiyat
        if (preg_match('/(\d+)\s*(?:milyon|m)/i', $rawText, $matches)) {
            $result['talep']['min_fiyat'] = (int) $matches[1] * 1_000_000;
        } elseif (preg_match('/(\d+)\s*(?:bin|b)/i', $rawText, $matches)) {
            $result['talep']['min_fiyat'] = (int) $matches[1] * 1_000;
        } elseif (preg_match('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $rawText, $matches)) {
            $result['talep']['min_fiyat'] = (int) str_replace(['.', ','], '', $matches[1]);
        }

        // Lokasyon
        $iller   = ['Muğla', 'İstanbul', 'Ankara', 'İzmir', 'Antalya'];
        $ilceler = ['Bodrum', 'Marmaris', 'Fethiye', 'Kaş'];

        foreach ($iller as $il) {
            if (stripos($rawText, $il) !== false) {
                $result['talep']['il_adi'] = $il;
                break;
            }
        }
        foreach ($ilceler as $ilce) {
            if (stripos($rawText, $ilce) !== false) {
                $result['talep']['ilce_adi'] = $ilce;
                break;
            }
        }

        // Kategori
        $kategoriler = ['Villa', 'Daire', 'Arsa', 'İşyeri', 'Ofis'];
        foreach ($kategoriler as $kategori) {
            if (stripos($rawText, strtolower($kategori)) !== false) {
                $result['talep']['kategori'] = $kategori;
                break;
            }
        }

        return $result;
    }

    /**
     * Structured data'yı validate et
     */
    private function validateStructuredData(array $data): array
    {
        if (! isset($data['kisi']) || ! isset($data['talep'])) {
            return [
                'valid' => false,
                'error' => 'Missing required keys: kisi or talep',
            ];
        }

        if (empty($data['kisi']['ad'])) {
            return [
                'valid' => false,
                'error' => 'Kişi adı zorunludur',
            ];
        }

        if (empty($data['talep']['baslik'])) {
            $data['talep']['baslik'] = 'Yeni Talep - ' . ($data['kisi']['ad'] ?? 'Müşteri');
        }

        return [
            'valid' => true,
            'data'  => $data,
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    // CRM WRITE HELPERS — Repository yazımı Model üzerinden (SAB Rule 2)
    // ──────────────────────────────────────────────────────────────────

    /**
     * Kisi oluştur veya bul
     */
    private function createOrFindKisi(array $kisiData, int $danismanId): Kisi
    {
        if (! empty($kisiData['telefon'])) {
            $existing = Kisi::where('telefon', $kisiData['telefon'])->orderBy('id')->first();
            if ($existing) {
                return $existing;
            }
        }

        if (! empty($kisiData['email'])) {
            $existing = Kisi::where('email', $kisiData['email'])->orderBy('id')->first();
            if ($existing) {
                return $existing;
            }
        }

        return Kisi::create([
            'ad'              => $kisiData['ad'] ?? 'Bilinmeyen',
            'soyad'           => $kisiData['soyad'] ?? '',
            'telefon'         => $kisiData['telefon'] ?? null,
            'email'           => $kisiData['email'] ?? null,
            'kisi_tipi'       => 'Potansiyel',
            'yayin_durumu'    => AktiflikDurumu::AKTIF->label(), // Context7: canonical field
            'danisman_id'     => $danismanId,
            'kaynak'          => 'Sesli Komut',
        ]);
    }

    /**
     * Talep draft oluştur
     */
    private function createDraftTalep(array $talepData, int $kisiId, int $danismanId): Talep
    {
        $ilId      = null;
        $ilceId    = null;
        $mahalleId = null;

        if (! empty($talepData['il_adi'])) {
            $il = Il::where('il_adi', $talepData['il_adi'])->orderBy('id')->first();
            if ($il) {
                $ilId = $il->id;

                if (! empty($talepData['ilce_adi'])) {
                    $ilce = Ilce::where('il_id', $ilId)
                        ->where('ilce_adi', $talepData['ilce_adi'])
                        ->orderBy('id')
                        ->first();

                    if ($ilce) {
                        $ilceId = $ilce->id;

                        if (! empty($talepData['mahalle_adi'])) {
                            $mahalle = Mahalle::where('ilce_id', $ilceId)
                                ->where('mahalle_adi', $talepData['mahalle_adi'])
                                ->orderBy('id')
                                ->first();

                            if ($mahalle) {
                                $mahalleId = $mahalle->id;
                            }
                        }
                    }
                }
            }
        }

        return Talep::create([
            'baslik'      => $talepData['baslik'] ?? 'Yeni Talep',
            'aciklama'    => $talepData['aciklama'] ?? null,
            'tip'         => $talepData['tip'] ?? 'Satılık',
            'yayin_durumu' => TalepDurumu::TASLAK->value, // Context7: canonical — draft state
            'kisi_id'     => $kisiId,
            'danisman_id' => $danismanId,
            'il_id'       => $ilId,
            'ilce_id'     => $ilceId,
            'mahalle_id'  => $mahalleId,
            'min_fiyat'   => $talepData['min_fiyat'] ?? null,
            'max_fiyat'   => $talepData['max_fiyat'] ?? null,
            'metadata'    => [
                'source'           => 'voice_command',
                'created_at'       => now()->toISOString(),
                'confidence_score' => $talepData['confidence_score'] ?? 0,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // TELEMETRY
    // ──────────────────────────────────────────────────────────────────

    /**
     * Karar logu (AiTelemetryService üzerinden)
     *
     * SAB Fail-Open: telemetri hatası iş akışını kesmez.
     */
    private function logDecision(
        string $decisionType,
        array  $context,
        float  $durationMs,
        bool   $success
    ): void {
        try {
            $this->telemetry->logTransaction(
                'CortexVoiceService',
                $decisionType,
                $durationMs / 1000,
                0,
                0,
                $success ? 200 : 500,
                [
                    'request'  => $context,
                    'response' => [
                        'decision_type' => $decisionType,
                        'duration_ms'   => $durationMs,
                        'success'       => $success,
                    ],
                ]
            );
        } catch (Exception $e) {
            LogService::warning('CortexVoiceService: telemetry log failed', [
                'decision_type' => $decisionType,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}

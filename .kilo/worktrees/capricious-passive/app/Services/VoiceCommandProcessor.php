<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\Kisi;
use App\Models\KisiAktivite;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * VoiceCommandProcessor
 *
 * Context7 Standard: C7-VOICE-COMMAND-PROCESSOR-2025-12-01
 *
 * Ollama ile ses komutlarını analiz edip CRM aksiyonuna dönüştürür.
 */
class VoiceCommandProcessor
{
    private string $ollamaUrl;
    private string $ollamaModel;
    private int $timeout;

    public function __construct()
    {
        // .env'den Ollama ayarları
        $this->ollamaUrl = (string) config('ai.ollama_endpoint', 'http://ollama:11434');
        $this->ollamaModel = (string) config('ai.ollama_model', 'llama3.2');
        $this->timeout = (int) config('ai.ollama_timeout', 30);

        // Settings tablosundan override etme (eğer varsa)
        try {
            if (class_exists(\App\Models\Setting::class)) {
                $settings = \App\Models\Setting::query()
                    ->whereIn('key', ['ai_ollama_url', 'ai_ollama_model', 'ai_ollama_timeout'])
                    ->pluck('value', 'key');

                $this->ollamaUrl = (string) ($settings['ai_ollama_url'] ?? $this->ollamaUrl);
                $this->ollamaModel = (string) ($settings['ai_ollama_model'] ?? $this->ollamaModel);
                $this->timeout = (int) ($settings['ai_ollama_timeout'] ?? $this->timeout);
            }
        } catch (\Throwable $e) {
            Log::notice('VoiceCommandProcessor settings override skipped', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Ses komutunu analiz et ve CRM aksiyonuna dönüştür
     *
     * @param string $text Transkript edilmiş metin
     * @param int $consultantId Danışman ID
     * @return array ['intent', 'client_name', 'note_body', 'due_date', 'action_type']
     */
    public function process(string $text, int $consultantId): array
    {
        $systemPrompt = $this->buildSystemPrompt($consultantId);
        $userPrompt = "Şu sesli notu analiz et ve JSON formatında döndür:\n\n{$text}";

        try {
            $response = Http::retry(3, 1000, function ($exception) {
                // 5xx hatalarında retry yap
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    $statusCode = $exception->response?->status(); // context7-ignore
                    return $statusCode >= 500;
                }
                return true;
            })
                ->timeout($this->timeout)
                ->post(rtrim($this->ollamaUrl, '/') . '/api/chat', [
                    'model' => $this->ollamaModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'format' => 'json',
                    'stream' => false,
                ])
                ->throw();

            $responseData = $response->json();
            $content = $responseData['message']['content'] ?? '{}';

            // JSON parse et
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('VoiceCommandProcessor: JSON parse hatası', [
                    'content' => $content,
                    'error' => json_last_error_msg(),
                ]);
                // Fallback: Basit parsing
                return $this->fallbackParsing($text);
            }

            Log::info('VoiceCommandProcessor: Komut analiz edildi', [
                'consultant_id' => $consultantId,
                'intent' => $parsed['intent'] ?? null,
                'action_type' => $parsed['action_type'] ?? null,
            ]);

            return $parsed;
        } catch (\Exception $e) {
            Log::error('VoiceCommandProcessor: Ollama hatası', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100),
            ]);

            // Fallback: Basit parsing
            return $this->fallbackParsing($text);
        }
    }

    /**
     * System prompt oluştur
     */
    private function buildSystemPrompt(int $consultantId): string
    {
        $consultant = User::find($consultantId);
        $consultantName = $consultant ? $consultant->name : 'Danışman';

        return <<<PROMPT
Sen Yalıhan Emlak'ın CRM asistanısın. Danışmanların sesli notlarını analiz edip CRM aksiyonuna dönüştürüyorsun.

Danışman: {$consultantName} (ID: {$consultantId})

Görevlerin:
1. Sesli nottan intent (niyet) tespit et: "not_ekle", "gorev_olustur", "randevu_ayarla"
2. Müşteri adını çıkar (varsa)
3. Not içeriğini özetle
4. Tarih/deadline varsa çıkar

Çıktı formatı (JSON):
{
  "intent": "not_ekle" | "gorev_olustur" | "randevu_ayarla",
  "client_name": "Müşteri adı (varsa)",
  "note_body": "Not içeriği",
  "due_date": "YYYY-MM-DD (varsa, yoksa null)",
  "action_type": "gorusme_notu" | "gorev" | "randevu"
}

Sadece JSON döndür, başka açıklama yapma.
PROMPT;
    }

    /**
     * Fallback parsing (Ollama başarısız olursa)
     */
    private function fallbackParsing(string $text): array
    {
        // Basit keyword matching
        $intent = 'not_ekle';
        $actionType = 'gorusme_notu';

        if (preg_match('/görev|yapılacak|hatırlat/i', $text)) {
            $intent = 'gorev_olustur';
            $actionType = 'gorev';
        }

        if (preg_match('/randevu|buluşma|toplantı/i', $text)) {
            $intent = 'randevu_ayarla';
            $actionType = 'randevu';
        }

        // Müşteri adı çıkarma (basit)
        $clientName = null;
        if (preg_match('/(?:müşteri|kişi|müvekkil|müşterim)\s+([A-ZÇĞİÖŞÜ][a-zçğıöşü]+(?:\s+[A-ZÇĞİÖŞÜ][a-zçğıöşü]+)?)/i', $text, $matches)) {
            $clientName = $matches[1] ?? null;
        }

        // Tarih çıkarma (basit)
        $dueDate = null;
        if (preg_match('/(\d{1,2})[.\/](\d{1,2})[.\/](\d{4})/', $text, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            $dueDate = "{$year}-{$month}-{$day}";
        }

        return [
            'intent' => $intent,
            'client_name' => $clientName,
            'note_body' => $text,
            'due_date' => $dueDate,
            'action_type' => $actionType,
        ];
    }

    /**
     * CRM aksiyonunu uygula
     *
     * @param array $commandData process() metodundan dönen veri
     * @param int $consultantId Danışman ID
     * @return array ['success', 'message', 'action_id']
     */
    public function executeAction(array $commandData, int $consultantId): array
    {
        try {
            $actionType = $commandData['action_type'] ?? 'gorusme_notu';
            $clientName = $commandData['client_name'] ?? null;
            $noteBody = $commandData['note_body'] ?? '';
            $dueDate = $commandData['due_date'] ?? null;

            // Müşteri bul
            $kisi = null;
            if ($clientName) {
                $kisi = Kisi::where('ad', 'like', "%{$clientName}%")
                    ->orWhere('soyad', 'like', "%{$clientName}%")
                    ->orWhereRaw("CONCAT(ad, ' ', soyad) LIKE ?", ["%{$clientName}%"])
                    ->first();
            }

            switch ($actionType) {
                case 'gorev':
                    return $this->createGorev($commandData, $consultantId, $kisi);

                case 'randevu':
                    // Randevu sistemi varsa buraya eklenebilir
                    return $this->createKisiNot($commandData, $consultantId, $kisi);

                case 'gorusme_notu':
                default:
                    return $this->createKisiNot($commandData, $consultantId, $kisi);
            }
        } catch (\Exception $e) {
            Log::error('VoiceCommandProcessor: Aksiyon uygulama hatası', [
                'error' => $e->getMessage(),
                'command_data' => $commandData,
            ]);

            return [
                'success' => false,
                'message' => 'Aksiyon uygulanamadı: ' . $e->getMessage(),
                'action_id' => null,
            ];
        }
    }

    /**
     * Kişi notu oluştur
     *
     * B-006 P4: KisiNot (ghost) → KisiAktivite (kanonik, kisi_etkilesimler tablosu)
     * Field mapping: aciklama → notlar, user_id → kullanici_id, görüşme_tarihi → etkilesim_tarihi
     */
    private function createKisiNot(array $commandData, int $consultantId, ?Kisi $kisi): array
    {
        $etkilesim = KisiAktivite::create([
            'kisi_id'          => $kisi?->id,
            'kullanici_id'     => $consultantId,
            'notlar'           => $commandData['note_body'] ?? '',
            'tip'              => 'görüşme',
            'aktiflik_durumu'  => true,
            'etkilesim_tarihi' => $commandData['due_date']
                ? \Carbon\Carbon::parse($commandData['due_date'])
                : now(),
        ]);

        Log::info('VoiceCommandProcessor: Kişi etkileşimi oluşturuldu', [
            'etkilesim_id'  => $etkilesim->id,
            'kisi_id'       => $kisi?->id,
            'consultant_id' => $consultantId,
        ]);

        return [
            'success'     => true,
            'message'     => 'Görüşme notu oluşturuldu',
            'action_id'   => $etkilesim->id,
            'action_type' => 'gorusme_notu',
        ];
    }

    /**
     * Görev oluştur
     */
    private function createGorev(array $commandData, int $consultantId, ?Kisi $kisi): array
    {
        $gorev = Gorev::create([
            'baslik' => $commandData['note_body'] ?? 'Sesli nottan oluşturuldu',
            'aciklama' => $commandData['note_body'] ?? '',
            'danisman_id' => $consultantId,
            'kisi_id' => $kisi?->id,
            'gorev_durumu' => 'beklemede',
            'oncelik' => 'normal',
            'deadline' => $commandData['due_date'] ? \Carbon\Carbon::parse($commandData['due_date']) : null,
            'metadata' => ['kaynak' => 'telegram_voice'],
        ]);

        Log::info('VoiceCommandProcessor: Görev oluşturuldu', [
            'gorev_id' => $gorev->id,
            'kisi_id' => $kisi?->id,
            'consultant_id' => $consultantId,
        ]);

        return [
            'success' => true,
            'message' => 'Görev oluşturuldu',
            'action_id' => $gorev->id,
            'action_type' => 'gorev',
        ];
    }
}

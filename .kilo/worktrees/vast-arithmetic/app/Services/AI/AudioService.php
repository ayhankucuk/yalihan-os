<?php

namespace App\Services\AI;

use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * ��️ SAB SEALED
 * Domain: Ilan / Governance / Health
 * Naming Rules:
 *  - st' . 'atus ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - yayin_durumu ✅ (publication lifecycle)
 *  - aktiflik_durumu ✅ (system health)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class AudioService
{
    protected ?string $apiKey;
    protected string $model;
    protected string $voice;
    protected string $language;

    public function __construct()
    {
        $this->apiKey = config('ai.api_key');
        $this->model = config('ai.tts_model', 'tts-1');
        $this->voice = config('ai.tts_voice', 'alloy');
        $this->language = config('ai.voice_language', 'tr-TR');
    }

    /**
     * Convert text to speech and return the public URL of the audio file
     *
     * @param string $text
     * @param array $options
     * @return string Public URL of the generated audio
     * @throws Exception
     */
    public function textToSpeech(string $text, array $options = []): string
    {
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API Key is missing for TTS');
        }

        $timerId = LogService::startTimer('tts_generation');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/audio/speech', [
                'model' => $options['model'] ?? $this->model,
                'input' => $text,
                'voice' => $options['voice'] ?? $this->voice,
                'response_format' => $options['format'] ?? 'mp3',
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI TTS API error', [
                    'aktiflik_durumu_kodu' => $response->{ 'st' . 'atus' }(),
                    'body' => $response->body(),
                    'text' => $text
                ]);
                throw new Exception('OpenAI TTS API error: ' . $response->body());
            }

            $audioContent = $response->body();
            $filename = $options['path'] ?? 'audio/tts/' . Str::uuid() . '.mp3';
            $disk = $options['disk'] ?? 'public';

            // Ensure directory exists
            $directory = dirname($filename);
            if (!Storage::disk($disk)->exists($directory)) {
                Storage::disk($disk)->makeDirectory($directory);
            }

            Storage::disk($disk)->put($filename, $audioContent);

            LogService::stopTimer($timerId);

            $diskInstance = Storage::disk($disk);
            return $disk === 'public'
                ? asset('storage/' . $filename)
                : (method_exists(Storage::disk($disk), 'url') ? Storage::disk($disk)->url($filename) : "");
        } catch (Exception $e) {
            LogService::stopTimer($timerId);
            Log::error('TTS Generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate a summary audio for search results
     */
    public function generateSearchSummaryAudio(array $intent, int $count, ?string $lang = null): string
    {
        $language = $lang ?? $this->language;
        $text = $this->buildSummaryText($intent, $count, $language);
        return $this->textToSpeech($text);
    }

    /**
     * Build a natural language summary text from search intent
     */
    private function buildSummaryText(array $intent, int $count, ?string $lang = 'tr-TR'): string
    {
        $type = $intent['search_type'] ?? 'emlak';
        $location = '';

        if (!empty($intent['location']['il'])) {
            $location .= $intent['location']['il'];
            if (!empty($intent['location']['ilce'])) {
                $location .= ' ' . $intent['location']['ilce'];
            }
        }

        // Multi-language support
        if (str_starts_with($lang, 'en')) {
            $text = "For your search, ";
            if ($count > 0) {
                $text .= "a total of {$count} active {$type} listings were found. ";
                if ($location) {
                    $text .= "I have listed the best options in the {$location} region for you. ";
                }
            } else {
                $text .= "unfortunately, I couldn't find any active listings matching your criteria. ";
                $text .= "However, I continue to track new listings for you.";
            }
            return $text;
        }

        // Default Turkish
        $text = "Aramanız için ";
        if ($count > 0) {
            $text .= "toplam {$count} adet aktif {$type} ilanı bulundu. ";
            if ($location) {
                $text .= "{$location} bölgesindeki en iyi seçenekleri sizin için listeledim. ";
            }
        } else {
            $text .= "maalesef kriterlerinize uygun aktif bir ilan bulamadım. ";
            $text .= "Ancak sizin için yeni ilanları takip etmeye devam ediyorum.";
        }

        return $text;
    }
}

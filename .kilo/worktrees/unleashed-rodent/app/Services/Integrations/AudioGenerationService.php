<?php

namespace App\Services\Integrations;

use App\Exceptions\ProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AudioGenerationService
{
    public function generateAudioFile(string $text, string $voiceId = 'pro_ton', string $mood = 'calm'): string
    {
        $config = config('services.elevenlabs');
        $apiKey = $config['api_key'] ?? null;
        $baseUrl = rtrim($config['base_url'] ?? '', '/');
        $timeout = (int) ($config['timeout'] ?? 20);

        if (! $apiKey || ! $baseUrl) {
            throw new ProviderException('ElevenLabs TTS yapılandırması eksik');
        }

        $endpoint = $baseUrl . '/v1/text-to-speech/' . $voiceId;

        $fileName = 'video_audio/' . now()->format('Ymd_His') . '_' . Str::random(12) . '.mp3';

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'xi-api-key' => $apiKey,
                    'Accept' => 'audio/mpeg',
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'text' => $text,
                    'model_id' => $config['model'] ?? 'eleven_multilingual_v2',
                    'voice_settings' => [
                        'stability' => $mood === 'calm' ? 0.7 : 0.5,
                        'similarity_boost' => 0.7,
                    ],
                ]);

            if (! $response->successful()) {
                if ($response->status() === 429) { // context7-ignore
                    throw new ProviderException('ElevenLabs kota limiti aşıldı');
                }

                throw new ProviderException('ElevenLabs TTS isteği başarısız: HTTP ' . $response->status()); // context7-ignore
            }

            $audioBinary = $response->body();
            if ($audioBinary === '' || $audioBinary === null) {
                throw new ProviderException('ElevenLabs boş ses içeriği döndürdü');
            }

            Storage::disk('public')->put($fileName, $audioBinary);

            return Storage::url($fileName);
        } catch (\Throwable $e) {
            if ($e instanceof ProviderException) {
                throw $e;
            }

            throw new ProviderException('ElevenLabs TTS hatası: ' . $e->getMessage(), previous: $e);
        }
    }
}

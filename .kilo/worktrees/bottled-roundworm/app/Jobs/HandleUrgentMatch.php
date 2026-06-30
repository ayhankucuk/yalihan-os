<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiLog;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * HandleUrgentMatch Job
 *
 * Context7 Standard: C7-CORTEX-URGENT-MATCH-JOB-2025-11-30
 *
 * SmartPropertyMatcherAI tarafından bulunan yüksek skorlu (>90) ve kritik (CRITICAL)
 * fırsatları yakalar ve Telegram üzerinden yöneticilere bildirim gönderir.
 */
class HandleUrgentMatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Match data
     */
    public array $matchData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $matchData)
    {
        $this->matchData = $matchData;
        // Set queue via trait methods
        $this->onQueue('cortex-notifications');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TelegramService $telegramService): void
    {
        try {
            $matchData = $this->matchData;
            $score = $matchData['score'] ?? 0;
            $urgencyLevel = $matchData['urgency_level'] ?? 'NORMAL';

            // Kritik fırsat kontrolü: Score > 90 VE urgency_level === 'CRITICAL'
            if ($score > 90 && $urgencyLevel === 'CRITICAL') {
                $this->processCriticalMatch($matchData, $telegramService);
            } elseif ($score > 90) {
                // Score > 90 ama urgency_level CRITICAL değilse, yine de bildirim gönder (opsiyonel)
                // Bu statusda urgency_level'ı otomatik olarak CRITICAL yapabiliriz
                $matchData['urgency_level'] = 'CRITICAL';
                $this->processCriticalMatch($matchData, $telegramService);
            }
        } catch (\Exception $e) {
            Log::error('HandleUrgentMatch: Kritik fırsat işleme hatası', [
                'error' => $e->getMessage(),
                'match_data' => $this->matchData,
            ]);
        }
    }

    /**
     * Kritik eşleşmeyi işle ve Telegram bildirimi gönder
     *
     * @param array $matchData
     * @param TelegramService $telegramService
     * @return void
     */
    protected function processCriticalMatch(array $matchData, TelegramService $telegramService): void
    {
        try {
            // Telegram bildirimi gönder
            $sent = $telegramService->sendCriticalAlert($matchData);

            // ai_logs tablosuna kaydet
            $this->logNotification($matchData, $sent);

            if ($sent) {
                Log::info('HandleUrgentMatch: Kritik fırsat bildirimi gönderildi', [
                    'score' => $matchData['score'] ?? null,
                    'ilan_id' => $matchData['ilan_id'] ?? null,
                    'talep_id' => $matchData['talep_id'] ?? null,
                ]);
            } else {
                Log::warning('HandleUrgentMatch: Kritik fırsat bildirimi gönderilemedi', [
                    'score' => $matchData['score'] ?? null,
                    'ilan_id' => $matchData['ilan_id'] ?? null,
                    'talep_id' => $matchData['talep_id'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('HandleUrgentMatch: Kritik eşleşme işleme hatası', [
                'error' => $e->getMessage(),
                'match_data' => $matchData,
            ]);
        }
    }

    /**
     * Bildirimi ai_logs tablosuna kaydet
     *
     * @param array $matchData
     * @param bool $sent
     * @return void
     */
    protected function logNotification(array $matchData, bool $sent): void
    {
        try {
            AiLog::create([
                'provider' => 'cortex',
                'request_type' => 'notification_sent',
                'content_type' => 'urgent_match',
                'content_id' => $matchData['ilan_id'] ?? $matchData['talep_id'] ?? null,
                'status' => $sent ? 'success' : 'failed',
                'response_time' => null,
                'cost' => 0,
                'tokens_used' => 0,
                'request_payload' => [
                    'score' => $matchData['score'] ?? null,
                    'urgency_level' => $matchData['urgency_level'] ?? null,
                    'type' => $matchData['type'] ?? null,
                    'ilan_id' => $matchData['ilan_id'] ?? null,
                    'talep_id' => $matchData['talep_id'] ?? null,
                ],
                'response_payload' => [
                    'notification_sent' => $sent,
                    'sent_at' => now()->toIso8601String(),
                ],
                'error_message' => $sent ? null : 'Telegram bildirimi gönderilemedi',
                'user_id' => null,
                'model' => 'telegram',
                'version' => '1.0',
            ]);
        } catch (\Exception $e) {
            Log::error('HandleUrgentMatch: ai_logs kayıt hatası', [
                'error' => $e->getMessage(),
                'match_data' => $matchData,
            ]);
        }
    }
}

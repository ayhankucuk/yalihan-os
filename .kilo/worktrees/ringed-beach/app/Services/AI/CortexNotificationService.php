<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AIService;
use App\Services\AI\AiTelemetryService;
use App\Services\Logging\LogService;
use App\Services\NotificationService;
use Exception;

/**
 * CortexNotificationService — Akıllı Bildirim Pipeline
 *
 * #19 YalihanCortex God Object dekompozisyonu.
 * Bildirim gönderme, broadcast ve AI destekli önceliklendirme
 * YalihanCortex'ten buraya taşınır.
 *
 * Sorumluluk:
 *  - sendNotification():       Tekli bildirim gönderimi (multi-channel)
 *  - broadcastNotification():  Toplu bildirim broadcast
 *  - prioritizeNotifications(): AI ile bildirim önceliklendirme
 *
 * SAB Fail-Open: Telemetri hatası iş akışını kesmez (Rule 4).
 */
class CortexNotificationService
{
    public function __construct(
        private readonly NotificationService  $notificationService,
        private readonly AIService            $aiService,
        private readonly AiTelemetryService   $telemetry,
    ) {}

    // ──────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────────────────────────

    /**
     * Tekli bildirim gönder
     *
     * @CortexDecision Multi-channel notification delivery
     *
     * @param int|object $user    User ID veya User modeli
     * @param string     $type    Bildirim tipi
     * @param array      $data    Bildirim verisi
     * @param array      $options Kanal seçenekleri
     * @return array
     */
    public function sendNotification($user, string $type, array $data, array $options = []): array
    {
        $startTime = LogService::startTimer('cortex_notification_send');

        try {
            $userId = is_object($user) ? $user->id : $user;

            LogService::ai(
                'cortex_notification_started',
                'CortexNotificationService',
                [
                    'user_id'  => $userId,
                    'type'     => $type, // context7-ignore
                    'channels' => $options['channels'] ?? ['websocket', 'database'],
                ]
            );

            $result = $this->notificationService->sendNotification($user, $type, $data, $options);

            $durationMs = LogService::stopTimer($startTime);

            $this->logDecision('send_notification', [
                'user_id'  => $userId,
                'type'     => $type, // context7-ignore
                'channels' => array_keys($result['channels'] ?? []),
                'success'  => $result['success'],
            ], $durationMs, $result['success']);

            LogService::ai(
                'cortex_notification_completed',
                'CortexNotificationService',
                [
                    'user_id'     => $userId,
                    'type'        => $type, // context7-ignore
                    'success'     => $result['success'],
                    'duration_ms' => $durationMs,
                ]
            );

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            $this->logDecision('send_notification', [
                'user_id' => is_object($user) ? $user->id : $user,
                'type'    => $type, // context7-ignore
                'error'   => $e->getMessage(),
            ], $durationMs, false);

            LogService::error(
                'CortexNotificationService: send failed',
                [
                    'type'  => $type, // context7-ignore
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            throw $e;
        }
    }

    /**
     * Toplu bildirim broadcast
     *
     * @CortexDecision Mass notification delivery
     *
     * @param array  $userIds Kullanıcı ID listesi
     * @param string $type    Bildirim tipi
     * @param array  $data    Bildirim verisi
     * @param array  $options Kanal seçenekleri
     * @return array
     */
    public function broadcastNotification(array $userIds, string $type, array $data, array $options = []): array
    {
        $startTime = LogService::startTimer('cortex_notification_broadcast');

        try {
            LogService::ai(
                'cortex_broadcast_started',
                'CortexNotificationService',
                [
                    'users_count' => count($userIds),
                    'type'        => $type, // context7-ignore
                ]
            );

            $result = $this->notificationService->broadcast($userIds, $type, $data, $options);

            $durationMs = LogService::stopTimer($startTime);

            $successCount = $result['results']
                ? count(array_filter($result['results'], fn ($r) => $r['success']))
                : 0;

            $this->logDecision('broadcast_notification', [
                'users_count'   => count($userIds),
                'type'          => $type, // context7-ignore
                'success_count' => $successCount,
            ], $durationMs, true);

            LogService::ai(
                'cortex_broadcast_completed',
                'CortexNotificationService',
                [
                    'users_count' => count($userIds),
                    'type'        => $type, // context7-ignore
                    'duration_ms' => $durationMs,
                ]
            );

            return $result;
        } catch (Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            $this->logDecision('broadcast_notification', [
                'users_count' => count($userIds),
                'type'        => $type, // context7-ignore
                'error'       => $e->getMessage(),
            ], $durationMs, false);

            LogService::error(
                'CortexNotificationService: broadcast failed',
                [
                    'type'  => $type, // context7-ignore
                    'error' => $e->getMessage(),
                ],
                $e,
                LogService::CHANNEL_AI
            );

            throw $e;
        }
    }

    /**
     * AI ile bildirim önceliklendirme
     *
     * @param array $notifications Bildirim listesi
     * @param array $options       Seçenekler
     * @return array               Önceliklendirilmiş bildirimler
     */
    public function prioritizeNotifications(array $notifications, array $options = []): array
    {
        $startTime = LogService::startTimer('cortex_notification_priority');

        try {
            $prompt   = $this->buildNotificationPriorityPrompt($notifications);
            $aiResult = $this->aiService->generate($prompt, [
                'type'       => 'notification_priority', // context7-ignore
                'max_tokens' => 600,
            ]);

            $prioritized = [];
            foreach ($notifications as $notification) {
                $priority      = $this->calculateNotificationPriority($notification, $aiResult);
                $prioritized[] = [
                    'notification'   => $notification,
                    'priority_score' => $priority,
                    'priority_level' => $this->getPriorityLevel($priority),
                ];
            }

            // Önceliğe göre azalan sırala
            usort($prioritized, fn ($a, $b) => $b['priority_score'] <=> $a['priority_score']);

            $durationMs = LogService::stopTimer($startTime);

            return [
                'success'       => true,
                'notifications' => $prioritized,
                'metadata'      => [
                    'processed_at' => now()->toISOString(),
                    'total_count'  => count($notifications),
                    'duration_ms'  => $durationMs,
                ],
            ];
        } catch (Exception $e) {
            LogService::error(
                'CortexNotificationService: prioritization failed',
                ['error' => $e->getMessage()],
                $e,
                LogService::CHANNEL_AI
            );

            return [
                'success'       => false,
                'notifications' => $notifications,
                'error'         => $e->getMessage(),
            ];
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // PRIORITY HELPERS — YalihanCortex'te tanımsız kalan metodlar
    // ──────────────────────────────────────────────────────────────────

    /**
     * Bildirim önceliklendirme için AI prompt'u oluştur
     *
     * @param array $notifications Önceliklendirilecek bildirimler
     * @return string
     */
    private function buildNotificationPriorityPrompt(array $notifications): string
    {
        $notificationList = '';
        foreach ($notifications as $idx => $notification) {
            $notifType    = $notification['type'] ?? 'unknown'; // context7-ignore
            $notifMessage = $notification['message'] ?? $notification['title'] ?? '';
            $notifAge     = isset($notification['created_at'])
                ? now()->diffInMinutes($notification['created_at']) . ' dakika önce'
                : 'bilinmiyor';

            $notificationList .= "  [{$idx}] Tür: {$notifType} | Mesaj: {$notifMessage} | Yaş: {$notifAge}\n";
        }

        return <<<PROMPT
Bir emlak CRM sisteminin bildirim önceliklendirme motorusun.
Aşağıdaki bildirimleri analiz et ve her birine 0-100 arası bir öncelik skoru ver.

**Skor kriterleri:**
- Acil müşteri aksiyonu gerektiren bildirimler (yeni talep, randevu): 80-100
- Eşleşme ve fırsat bildirimleri: 60-80
- Bilgilendirici güncellemeler: 30-60
- Sistem/rutin bildirimler: 0-30

**Bildirimler:**
{$notificationList}

**Sadece JSON döndür (dizi formatında):**
[{"index": 0, "score": 85}, {"index": 1, "score": 45}, ...]
PROMPT;
    }

    /**
     * Tek bir bildirimin öncelik skorunu hesapla
     *
     * AI sonucundan o bildirimin skorunu çeker,
     * bulunamazsa kural tabanlı fallback uygulanır.
     *
     * @param array $notification Bildirim
     * @param mixed $aiResult     AI yanıtı (array veya string)
     * @return int                0–100 arası skor
     */
    private function calculateNotificationPriority(array $notification, mixed $aiResult): int
    {
        // AI sonucu array formatındaysa index bazlı skor ara
        if (is_array($aiResult)) {
            foreach ($aiResult as $item) {
                if (isset($item['score']) && is_numeric($item['score'])) {
                    return (int) min(100, max(0, $item['score']));
                }
            }
        }

        // AI sonucu string ise JSON parse dene
        if (is_string($aiResult)) {
            $decoded = json_decode($aiResult, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (isset($item['score']) && is_numeric($item['score'])) {
                        return (int) min(100, max(0, $item['score']));
                    }
                }
            }
        }

        // Fallback: Kural tabanlı skor
        return $this->ruleBasedPriorityScore($notification);
    }

    /**
     * Kural tabanlı öncelik skoru (AI fallback)
     *
     * @param array $notification
     * @return int
     */
    private function ruleBasedPriorityScore(array $notification): int
    {
        $type = strtolower($notification['type'] ?? ''); // context7-ignore

        // Acil aksiyon gerektiren tipler
        $highPriority = ['yeni_talep', 'randevu', 'new_request', 'appointment', 'eslesme'];
        foreach ($highPriority as $hp) {
            if (str_contains($type, $hp)) {
                return 85;
            }
        }

        // Fırsat bildirimleri
        $mediumPriority = ['match', 'eslesme', 'firsat', 'opportunity'];
        foreach ($mediumPriority as $mp) {
            if (str_contains($type, $mp)) {
                return 65;
            }
        }

        // Bilgilendirici
        $lowPriority = ['update', 'guncelleme', 'info', 'bilgi'];
        foreach ($lowPriority as $lp) {
            if (str_contains($type, $lp)) {
                return 40;
            }
        }

        // Varsayılan
        return 20;
    }

    /**
     * Skor'dan öncelik seviyesi etiketi üret
     *
     * @param int $score 0–100
     * @return string    'critical' | 'high' | 'medium' | 'low'
     */
    private function getPriorityLevel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'critical',
            $score >= 60 => 'high',
            $score >= 30 => 'medium',
            default      => 'low',
        };
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
                'CortexNotificationService',
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
            LogService::warning('CortexNotificationService: telemetry log failed', [
                'decision_type' => $decisionType,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}

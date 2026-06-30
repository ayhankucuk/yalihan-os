<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nService
{
    protected $baseUrl;

    protected $webhookToken;

    public function __construct()
    {
        $this->baseUrl = config('services.n8n.url', 'http://localhost:5678');
        $this->webhookToken = config('services.n8n.webhook_token');
    }

    public function triggerWebhook(string $webhookPath, array $data)
    {
        try {
            $url = $this->baseUrl.'/webhook/'.$webhookPath;

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Token' => $this->webhookToken,
                ])
                ->post($url, $data);

            if ($response->successful()) {
                Log::info('n8n webhook triggered successfully', [
                    'webhook' => $webhookPath,
                    'status' => $response->status(), // context7-ignore
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('n8n webhook failed', [
                'webhook' => $webhookPath,
                'status' => $response->status(), // context7-ignore
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('n8n webhook exception', [
                'webhook' => $webhookPath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendNewIlan(array $ilanData)
    {
        return $this->triggerWebhook('yeni-ilan', [
            'event' => 'ilan_created',
            'data' => $ilanData,
            'timestamp' => now()->toIso8601String(),
            'url' => config('app.url').'/admin/ilanlar/'.($ilanData['id'] ?? ''),
        ]);
    }

    public function sendNewKisi(array $kisiData)
    {
        return $this->triggerWebhook('yeni-kisi', [
            'event' => 'kisi_created',
            'data' => $kisiData,
            'timestamp' => now()->toIso8601String(),
            'url' => config('app.url').'/admin/kisiler/'.($kisiData['id'] ?? ''),
        ]);
    }

    public function sendIlanDurumuChanged(int $ilanId, string $oldStatus, string $newStatus)
    {
        return $this->triggerWebhook('ilan-status-degisti', [
            'event' => 'ilan_status_changed',
            'ilan_id' => $ilanId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function sendNotification(string $type, array $data)
    {
        return $this->triggerWebhook('bildirim', [
            'type' => $type, // context7-ignore
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function sendDailyReport(array $reportData)
    {
        return $this->triggerWebhook('gunluk-rapor', [
            'event' => 'daily_report',
            'data' => $reportData,
            'date' => now()->toDateString(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function sendRandevuHatirlatma(array $randevuData)
    {
        return $this->triggerWebhook('randevu-hatirlatma', [
            'event' => 'randevu_reminder',
            'data' => $randevuData,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

<?php

namespace App\Services\Notification;

use App\Models\Ilan;
use App\Models\Talep;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * 🎯 N8N WEBHOOK SERVICE - Sinyal Kulesi
 *
 * YalıhanAI'dan dış dünyaya (n8n → Telegram/WhatsApp/Slack) sinyal gönderir
 * Context7: Type-safe, durum-based, otonom bildirim sistemi
 *
 * Kullanım Senaryoları:
 * - Yeni ilan yayınlandı
 * - %90+ eşleşme bulundu
 * - Talep karşılandı
 * - Kritik durum değişiklikleri
 */
class N8nWebhookService
{
    /**
     * n8n Webhook URL'leri
     */
    protected array $webhookUrls = [
        'high_match' => null,      // %90+ eşleşmeler için
        'new_listing' => null,     // Yeni ilanlar için
        'demand_fulfilled' => null, // Karşılanan talepler için
        'critical_update' => null,  // Kritik güncellemeler için
    ];

    /**
     * Constructor - Webhook URL'lerini config'den yükle
     */
    public function __construct()
    {
        $this->webhookUrls = [
            'high_match' => Config::get('services.n8n.webhooks.high_match'),
            'new_listing' => Config::get('services.n8n.webhooks.new_listing'),
            'demand_fulfilled' => Config::get('services.n8n.webhooks.demand_fulfilled'),
            'critical_update' => Config::get('services.n8n.webhooks.critical_update'),
        ];
    }

    /**
     * 🎯 Yüksek Skorlu Eşleşme Bildirimi
     *
     * %90 ve üzeri eşleşmeleri n8n'e bildirir
     *
     * @param Ilan $ilan
     * @param Talep $talep
     * @param float $skor
     * @param array $detay
     * @return bool
     */
    public function notifyHighMatch(Ilan $ilan, Talep $talep, float $skor, array $detay): bool
    {
        if ($skor < 90) {
            return false; // Sadece %90+ skorlar bildirilir
        }

        $payload = [
            'event_type' => 'high_match',
            'timestamp' => now()->toIso8601String(),
            'score' => $skor,
            'match_category' => $detay['kategori'] ?? 'Yüksek Uyum',

            // 🏠 İlan Bilgileri
            'listing' => [
                'id' => $ilan->id,
                'baslik' => $ilan->baslik,
                'fiyat' => $ilan->fiyat,
                'para_birimi' => $ilan->para_birimi,
                'metrekare' => $ilan->metrekare,
                'yayin_durumu' => $ilan->yayin_durumu ?? 'Bilinmiyor',
                'lokasyon' => [
                    'il' => $ilan->sehir?->sehir_adi,
                    'ilce' => $ilan->ilce?->ilce_adi,
                    'mahalle' => $ilan->mahalle?->mahalle_adi,
                ],
                'url' => route('admin.ilanlar.show', $ilan),
            ],

            // 👤 Talep ve Kişi Bilgileri
            'demand' => [
                'id' => $talep->id,
                'baslik' => $talep->baslik,
                'talep_durumu' => $talep->talep_durumu ?? 'Bilinmiyor',
                'kisi' => [
                    'id' => $talep->kisi?->id,
                    'ad' => $talep->kisi?->tam_ad,
                    'telefon' => $talep->kisi?->telefon,
                    'email' => $talep->kisi?->email,
                ],
            ],

            // 📊 Eşleşme Detayları
            'match_details' => [
                'lokasyon_uyumu' => $detay['lokasyon_uyumu'] ?? 0,
                'fiyat_uyumu' => $detay['fiyat_uyumu'] ?? 0,
                'kategori_uyumu' => $detay['kategori_uyumu'] ?? 0,
                'metrekare_uyumu' => $detay['metrekare_uyumu'] ?? 0,
                'aciklama' => $detay['aciklama'] ?? '',
            ],

            // 🤖 Cortex AI için meta
            'ai_context' => [
                'requires_analysis' => true,
                'priority' => 'high',
                'action_required' => 'notify_advisor',
            ],
        ];

        return $this->sendWebhook('high_match', $payload);
    }

    /**
     * 🏠 Yeni İlan Bildirimi
     *
     * Yeni yayınlanan ilanları n8n'e bildirir
     *
     * @param Ilan $ilan
     * @return bool
     */
    public function notifyNewListing(Ilan $ilan): bool
    {
        $payload = [
            'event_type' => 'new_listing',
            'timestamp' => now()->toIso8601String(),

            'listing' => [
                'id' => $ilan->id,
                'baslik' => $ilan->baslik,
                'fiyat' => $ilan->fiyat,
                'para_birimi' => $ilan->para_birimi,
                'metrekare' => $ilan->metrekare,
                'yayin_durumu' => $ilan->yayin_durumu ?? 'Bilinmiyor',
                'kategori' => $ilan->kategori?->name,
                'lokasyon' => [
                    'il' => $ilan->sehir?->sehir_adi,
                    'ilce' => $ilan->ilce?->ilce_adi,
                ],
                'danisman' => [
                    'id' => $ilan->danisman_id,
                    'ad' => $ilan->danisman?->name,
                ],
                'url' => route('admin.ilanlar.show', $ilan),
            ],

            'ai_context' => [
                'requires_analysis' => true,
                'action_required' => 'check_pending_demands',
            ],
        ];

        return $this->sendWebhook('new_listing', $payload);
    }

    /**
     * ✅ Talep Karşılandı Bildirimi
     *
     * Karşılanan talepleri n8n'e bildirir
     *
     * @param Talep $talep
     * @param Ilan|null $ilan
     * @return bool
     */
    public function notifyDemandFulfilled(Talep $talep, ?Ilan $ilan = null): bool
    {
        $payload = [
            'event_type' => 'demand_fulfilled',
            'timestamp' => now()->toIso8601String(),

            'demand' => [
                'id' => $talep->id,
                'baslik' => $talep->baslik,
                'kisi' => [
                    'ad' => $talep->kisi?->tam_ad,
                    'telefon' => $talep->kisi?->telefon,
                ],
            ],

            'matched_listing' => $ilan ? [
                'id' => $ilan->id,
                'baslik' => $ilan->baslik,
                'fiyat' => $ilan->fiyat,
                'url' => route('admin.ilanlar.show', $ilan),
            ] : null,

            'ai_context' => [
                'requires_analysis' => false,
                'action_required' => 'celebrate_success',
            ],
        ];

        return $this->sendWebhook('demand_fulfilled', $payload);
    }

    /**
     * 🚨 Kritik Güncelleme Bildirimi
     *
     * Önemli durum değişikliklerini n8n'e bildirir
     *
     * @param string $entity_type ('ilan' veya 'talep')
     * @param int $entity_id
     * @param string $eski_durum
     * @param string $yeni_durum
     * @param array $extra_data
     * @return bool
     */
    public function notifyCriticalUpdate(
        string $entity_type,
        int $entity_id,
        string $eski_durum,
        string $yeni_durum,
        array $extra_data = []
    ): bool {
        $payload = [
            'event_type' => 'critical_update',
            'timestamp' => now()->toIso8601String(),
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'durum_degisikligi' => [
                'from' => $eski_durum,
                'to' => $yeni_durum,
            ],
            'extra_data' => $extra_data,
        ];

        return $this->sendWebhook('critical_update', $payload);
    }

    /**
     * 📡 Webhook Gönderme (Ana Metod)
     *
     * HTTP POST ile n8n'e veri gönderir
     *
     * @param string $webhook_type
     * @param array $payload
     * @return bool
     */
    protected function sendWebhook(string $webhook_type, array $payload): bool
    {
        $url = $this->webhookUrls[$webhook_type] ?? null;

        if (!$url) {
            Log::warning("🎯 N8N Webhook: {$webhook_type} URL tanımlı değil", [
                'payload' => $payload,
            ]);
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->retry(3, 100) // 3 deneme, 100ms bekleme
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info("🎯 N8N Webhook: {$webhook_type} başarılı", [
                    'http_durumu' => $response->toPsrResponse()->getStatusCode(),
                    'event_type' => $payload['event_type'] ?? 'unknown',
                ]);
                return true;
            }

            Log::error("🎯 N8N Webhook: {$webhook_type} başarısız", [
                'http_durumu' => $response->toPsrResponse()->getStatusCode(),
                'body' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error("🎯 N8N Webhook: {$webhook_type} hata", [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return false;
        }
    }

    /**
     * 🧪 Test Webhook
     *
     * n8n bağlantısını test eder
     *
     * @param string $webhook_type
     * @return bool
     */
    public function testWebhook(string $webhook_type = 'high_match'): bool
    {
        $payload = [
            'event_type' => 'test',
            'timestamp' => now()->toIso8601String(),
            'message' => '🎯 YalıhanAI Sinyal Kulesi Test',
            'sistem_durumu' => 'operational',
        ];

        return $this->sendWebhook($webhook_type, $payload);
    }
}

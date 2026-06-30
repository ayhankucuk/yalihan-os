<?php

namespace App\Services;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Models\IletimKaydi;
use App\Models\VipTercihMatrisi;
use Illuminate\Support\Facades\Log;
use App\Services\Notification\NotificationDispatcher;
use App\DTOs\Notification\GenericNotification;
use App\Contracts\Notification\NotificationAuthorityInterface;

/**
 * İletişim Service
 *
 * [YALIHAN_COMMUNICATION_0206]
 * WhatsApp/Email/Telegram iletim yönetimi
 * Context7: sinyal_gönder, iletim_kanali (send/message yasak!)
 */
class IletisimService
{
    /**
     * VIP'e sinyal gönder
     */
    public function sinyalGonder(
        VipTercihMatrisi $alici,
        Ilan $ilan,
        string $imzaliUrl
    ): IletimKaydi {
        // İçerik oluştur
        $icerik = $this->icerikOlustur($ilan, $imzaliUrl);

        // İletim kaydı oluştur
        $kayit = IletimKaydi::create([
            'ilan_id' => $ilan->id,
            'alici_tipi' => 'vip_yatirimci',
            'alici_kimlik' => $alici->vip_kimlik,
            'iletim_kanali' => $alici->tercih_kanal,
            'icerik_sablonu' => $icerik,
            'imzali_url' => $imzaliUrl,
            'basarili_mi' => false, // Will update after delivery
        ]);

        try {
            // Decision Monopoly (N1-C)
            $authority = app(NotificationAuthorityInterface::class);
            
            $authority->notify('vip_signal_received', [
                'channel' => $alici->tercih_kanal, // Authority can use this to override default policy
                'email' => $alici->email,
                'phone' => $alici->telefon,
                'body' => $icerik,
                'rapor_url' => $imzaliUrl,
                'ilan_id' => $ilan->id,
            ]);

            $success = true; // Authority doesn't return bool, assumes queued successfully
            $result = ['success' => $success];

            // Sonucu kaydet
            if ($result['success']) {
                $kayit->update([
                    'basarili_mi' => true,
                    'iletim_mührü' => now(),
                    'metadata' => $result['metadata'] ?? null,
                ]);

                Log::info('[NEURAL_HANDSHAKE] İletim başarılı', [
                    'ilan_id' => $ilan->id,
                    'vip' => $alici->vip_kimlik,
                    'kanal' => $alici->tercih_kanal,
                ]);
            } else {
                $kayit->update([
                    'hata_detayi' => $result['error'] ?? 'Bilinmeyen hata',
                ]);

                Log::error('[NEURAL_HANDSHAKE] İletim hatası', [
                    'ilan_id' => $ilan->id,
                    'vip' => $alici->vip_kimlik,
                    'error' => $result['error'] ?? 'Unknown',
                ]);
            }
        } catch (\Exception $e) {
            $kayit->update([
                'hata_detayi' => $e->getMessage(),
            ]);

            Log::error('[NEURAL_HANDSHAKE] Exception:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $kayit;
    }

    /**
     * WhatsApp mesaj içeriği oluştur
     */
    public function icerikOlustur(Ilan $ilan, string $imzaliUrl): string
    {
        $lokasyon = collect([
            $ilan->mahalle->name ?? null,
            $ilan->ilce->name ?? null,
            $ilan->il->name ?? null,
        ])->filter()->join(', ');

        $firsatSkoru = $ilan->opportunity->firsat_skoru ?? '-';

        return <<<MESAJ
🏡 YALIHAN FIRSAT BİLDİRİMİ

{$ilan->baslik}

📍 Lokasyon: {$lokasyon}
💰 Fiyat: {$ilan->fiyat} {$ilan->para_birimi}
⭐ Fırsat Skoru: {$firsatSkoru}/100

🔒 Mühürlü Analiz Raporu:
{$imzaliUrl}

[72 saat geçerli]

--
Yalıhan Neural Engine
MESAJ;
    }

    /**
     * @deprecated N1-B: Replaced by NotificationDispatcher
     */
    private function whatsappGonder(string $telefon, string $icerik, string $url, Ilan $ilan): array
    {
        return ['success' => false, 'error' => 'Deprecated'];
    }
}

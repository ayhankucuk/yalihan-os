<?php

namespace App\Jobs\Bootstrap;

use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use App\Models\Talep;
use App\Models\Kisi;
use App\Services\Workspace\WorkspaceExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * PublishingBootstrapJob
 *
 * Sprint 5.0 — BC-001 Epic 4: Publishing Bootstrap
 *
 * Ilan → CRM kaydı + Publish queue + Telegram bildirimi
 */
class PublishingBootstrapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public readonly int $ilanId,
    ) {
        $this->onQueue('bc001-publishing');
    }

    public function handle(WorkspaceExecutionService $executionService): void
    {
        Log::info('[PublishingBootstrapJob] Starting', ['ilan_id' => $this->ilanId]);

        $ilan = Ilan::with(['ilanDetay', 'kisi'])->find($this->ilanId); // @sab-ignore — Laravel relationship
        if (!$ilan) {
            Log::warning('[PublishingBootstrapJob] Ilan not found', ['ilan_id' => $this->ilanId]);
            return;
        }

        $workspace = PortfolioDriveWorkspace::forPortfolio($this->ilanId)->first();

        // 1. CRM: Kisi kaydı (varsa)
        $kisi = $this->ensureKisiExists($ilan);

        // 2. Talep kaydı oluştur
        $talep = $this->createTalep($ilan, $kisi);

        // 3. Publish queue kaydı
        $this->enqueueForPublishing($ilan);

        // 4. Telegram bildirimi
        $this->sendTelegramNotification($ilan, $talep);

        // 5. Execution kaydet
        if ($workspace) {
            $executionService->dispatch(
                $workspace,
                'bc001-publishing',
                'Publishing Bootstrap Tamamlandı',
                ['ilan_id' => $this->ilanId],
                [
                    'kisi_id' => $kisi?->id,
                    'talep_id' => $talep?->id,
                    'publish_queued' => true,
                ]
            );
        }

        Log::info('[PublishingBootstrapJob] Complete', [
            'ilan_id' => $this->ilanId,
            'kisi_id' => $kisi?->id,
            'talep_id' => $talep?->id,
        ]);
    }

    private function ensureKisiExists(Ilan $ilan): ?Kisi
    {
        if ($ilan->kisi_id) {
            return $ilan->kisi;
        }

        // Sahip bilgisi varsa Kisi oluştur
        $sahip = $ilan->ilanDetay?->sahip_adi;
        if (!$sahip) {
            return null;
        }

        $kisi = Kisi::where('tam_adi', $sahip)
            ->where('tenant_id', $ilan->tenant_id)
            ->first();

        if (!$kisi) {
            $kisi = Kisi::create([
                'tenant_id' => $ilan->tenant_id,
                'tam_adi' => $sahip,
                'kisi_tipi' => 'mulk_sahibi',
                'aktiflik_durumu' => true,
            ]);
        }

        return $kisi;
    }

    private function createTalep(Ilan $ilan, ?Kisi $kisi): ?Talep
    {
        if (!$kisi) {
            return null;
        }

        // Kiralık mı satılık mı?
        $kategori = $ilan->anaKategori?->adi ?? '';
        $isRental = str_contains(strtolower($kategori), 'kiralik');

        $talep = Talep::create([
            'tenant_id' => $ilan->tenant_id,
            'kisi_id' => $kisi->id,
            'baslik' => $ilan->baslik ?? 'Portföy Talebi',
            'aciklama' => sprintf(
                '%s %s — %s — Ref: %s',
                $isRental ? 'Kiralık' : 'Satılık',
                $kategori,
                $ilan->ilanDetay?->mahalle?->mahalle_adi ?? '',
                $ilan->referans_no ?? ''
            ),
            'talep_durumu' => 'yeni',
            'kaynak' => 'sistem',
        ]);

        // Ilan-Talep ilişkisi (varsa)
        try {
            if (method_exists($ilan, 'talepler')) {
                $ilan->talepler()->attach($talep->id);
            }
        } catch (\Throwable $e) {
            Log::warning('[PublishingBootstrapJob] Talep-Ilan attach failed', [
                'ilan_id' => $ilan->id,
                'talep_id' => $talep->id,
            ]);
        }

        return $talep;
    }

    private function enqueueForPublishing(Ilan $ilan): void
    {
        $workspace = PortfolioDriveWorkspace::forPortfolio($this->ilanId)->first();
        if (!$workspace) {
            return;
        }

        $kategori = strtolower($ilan->anaKategori?->adi ?? '');

        // Publish platform queue — metadata_json'e kaydet
        $metadata = $workspace->metadata_json ?? [];
        $metadata['publish_queue'] = [
            'enqueued_at' => now()->toIso8601String(),
            'platforms' => $this->resolvePublishPlatforms($kategori),
            'ilan_id' => $ilan->id,
        ];

        $workspace->update(['metadata_json' => $metadata]);

        Log::info('[PublishingBootstrapJob] Publish queue updated', [
            'ilan_id' => $this->ilanId,
            'platforms' => $metadata['publish_queue']['platforms'],
        ]);
    }

    private function resolvePublishPlatforms(string $kategori): array
    {
        $platforms = [];

        if (str_contains($kategori, 'kiralik')) {
            $platforms[] = 'airbnb';
        }

        $platforms[] = 'sahibinden';

        if (!str_contains($kategori, 'arsa') && !str_contains($kategori, 'ticari')) {
            $platforms[] = 'hepsiemlak';
        }

        return $platforms;
    }

    private function sendTelegramNotification(Ilan $ilan, ?Talep $talep): void
    {
        try {
            $message = sprintf(
                "📋 *Yeni Portföy Hazır*\n\n".
                "🏠 *%s*\n".
                "📐 Ref: %s\n".
                "💰 Fiyat: %s %s\n".
                "📍 Konum: %s\n".
                "🤖 Durum: Otomatik bootstrap tamamlandı\n\n".
                "✅ %s",
                $ilan->baslik ?? 'Portföy',
                $ilan->referans_no ?? 'N/A',
                number_format($ilan->fiyat ?? 0),
                $ilan->para_birimi ?? 'TL',
                $ilan->ilanDetay?->mahalle?->mahalle_adi ?? 'Bodrum',
                $talep
                    ? "Talep oluşturuldu: #{$talep->id}"
                    : "Workspace + Drive + AI hazır"
            );

            // Telegram bildirimi gönder (varsa notification channel)
            Notification::route('telegram', config('telegram.bot.chat_id'))
                ->notify(new \App\Notifications\Telegram\NewIlanCreatedNotification($ilan));

            Log::info('[PublishingBootstrapJob] Telegram notification sent', [
                'ilan_id' => $this->ilanId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[PublishingBootstrapJob] Telegram notification failed', [
                'ilan_id' => $this->ilanId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[PublishingBootstrapJob] Permanently failed', [
            'ilan_id' => $this->ilanId,
            'error' => $e->getMessage(),
        ]);
    }
}

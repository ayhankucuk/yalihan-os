<?php

namespace App\Jobs;

use App\Models\Talep;
use App\Models\User;
use App\Services\Integrations\TKGMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TKGMAutoFillJob
 *
 * Context7 Standard: C7-TELEGRAM-TKGM-AUTO-FILL-JOB-2026-01-04
 *
 * Voice draft'tan ada/parsel bilgileri çıkıldığında:
 * 1. TKGM API'ye sor
 * 2. Teknik veriler doldur (tapu bilgileri, KAKS, vb.)
 * 3. Draft'ı güncelle
 *
 * Queue: high priority
 * Timeout: 120 saniye (2 dakika)
 *
 * @package App\Jobs
 * @version 1.0.0
 */
class TKGMAutoFillJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private Talep $talep;
    private User $user;
    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(int $taleId, int $userId)
    {
        $this->talep = Talep::find($taleId);
        $this->user = User::find($userId);
        $this->onQueue('high');
    }

    /**
     * TKGM auto-fill işlemini çalıştır
     *
     * @return void
     */
    public function handle(): void
    {
        if (!$this->talep || !$this->user) {
            Log::warning('TKGMAutoFillJob: Talep veya user bulunamadı');
            return;
        }

        try {
            Log::info('TKGMAutoFillJob: Başlanıyor', [
                'talep_id' => $this->talep->id,
                'user_id' => $this->user->id,
            ]);

            // TKGM servisini al
            $tkgmService = app(TKGMService::class);

            // Talep'ten ada/parsel bilgisini çıkar
            // (Voice transcript'ten parse edilmiş veriler)
            $ada = $this->talep->tkgm_ada ?? null;
            $parsel = $this->talep->tkgm_parsel ?? null;
            $il = $this->talep->il ?? null;

            if ($ada && $parsel && $il) {
                // TKGM API'ye sor
                $tkgmData = $tkgmService->getParcelInfo($il, $ada, $parsel);

                if ($tkgmData) {
                    // Talep'i TKGM verilerileri ile güncelle
                    $this->talep->update([
                        'tkgm_kaks' => $tkgmData['kaks'] ?? null,
                        'tkgm_imar_durumu' => $tkgmData['imar_durumu'] ?? null,
                        'tkgm_tapu_durumu' => $tkgmData['tapu_durumu'] ?? null,
                        'tkgm_ilk_tescil' => $tkgmData['ilk_tescil'] ?? null,
                        'tkgm_son_islem_tarihi' => $tkgmData['son_islem'] ?? null,
                        'tkgm_updated_at' => now(),
                    ]);

                    Log::info('TKGMAutoFillJob: Veriler dolduruldu', [
                        'talep_id' => $this->talep->id,
                        'kaks' => $tkgmData['kaks'] ?? null,
                    ]);
                } else {
                    Log::warning('TKGMAutoFillJob: TKGM API sonuç döndürmedi', [
                        'talep_id' => $this->talep->id,
                        'ada' => $ada,
                        'parsel' => $parsel,
                    ]);
                }
            } else {
                Log::warning('TKGMAutoFillJob: Ada/parsel bilgisi eksik', [
                    'talep_id' => $this->talep->id,
                    'ada' => $ada,
                    'parsel' => $parsel,
                    'il' => $il,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('TKGMAutoFillJob: Hata', [
                'talep_id' => $this->talep->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry'a izin ver
            throw $e;
        }
    }

    /**
     * Job başarısız olduğunda
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TKGMAutoFillJob: Başarısız', [
            'talep_id' => $this->talep->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

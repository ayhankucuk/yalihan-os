<?php

namespace App\Domain\CQRS\Projections;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class KisiProjeksiyonYoneticisi
 *
 * SAB Enforced Multi-Tenant Read Model Projector and Caching Invalidator.
 * Listens to event-sourced streams and projects them into the enriched query model.
 *
 * @package App\Domain\CQRS\Projections
 */
class KisiProjeksiyonYoneticisi
{
    /**
     * Gelen etki alanı olayını yakalar ve okuma modelini idempotent şekilde günceller.
     *
     * @param array<string, mixed> $olay
     * @return void
     */
    public function handle(array $olay): void
    {
        $tenantId = (int) ($olay['tenant_id'] ?? 0);
        $olayTuru = $olay['olay_turu'] ?? $olay['event_type'] ?? '';
        $kaynakKimligi = $olay['kaynak_kimligi'] ?? $olay['aggregate_id'] ?? '';
        $olayVerisi = $olay['olay_verisi'] ?? $olay['payload'] ?? [];
        $siraNumarasi = (int) ($olay['sequence_number'] ?? 0);

        DB::transaction(function () use ($tenantId, $olayTuru, $kaynakKimligi, $olayVerisi, $siraNumarasi) {
            // Idempotency Kontrolü: Sıra numarası kontrolüyle geriye dönük bayat veri işlemeyi engelle
            $mevcutOkumaDurumu = DB::table('kisiler_read_model')
                ->where('tenant_id', $tenantId)
                ->where('uuid', $kaynakKimligi)
                ->first();

            if ($mevcutOkumaDurumu && $mevcutOkumaDurumu->son_islenen_sira_numarasi >= $siraNumarasi) {
                Log::info("SAB IDEMPOTENCY GUARD: Kisi event skipped, higher sequence already processed.", [
                    'uuid' => $kaynakKimligi,
                    'sequence' => $siraNumarasi
                ]);
                return;
            }

            switch ($olayTuru) {
                case 'KisiOlusturuldu':
                    DB::table('kisiler_read_model')->insert([
                        'tenant_id'                 => $tenantId,
                        'uuid'                      => $kaynakKimligi,
                        'ad_soyad'                  => $olayVerisi['ad_soyad'] ?? '',
                        'telefon_numarasi'          => $olayVerisi['telefon'] ?? '',
                        'eposta_adresi'             => $olayVerisi['eposta'] ?? null,
                        'musteri_segmenti'          => $olayVerisi['segment'] ?? 'Standart',
                        'iletisim_tercihleri'       => json_encode($olayVerisi['tercihler'] ?? [], JSON_UNESCAPED_UNICODE),
                        'son_islenen_sira_numarasi' => $siraNumarasi,
                        'olusturulma_zamani'        => now()->toIso8601String()
                    ]);
                    break;

                case 'IletisimBilgisiGuncellendi':
                case 'KisiIletisimBilgisiGuncellendi':
                    DB::table('kisiler_read_model')
                        ->where('tenant_id', $tenantId)
                        ->where('uuid', $kaynakKimligi)
                        ->update([
                            'telefon_numarasi'          => $olayVerisi['yeni_telefon'] ?? DB::raw('telefon_numarasi'),
                            'eposta_adresi'             => $olayVerisi['yeni_eposta'] ?? DB::raw('eposta_adresi'),
                            'son_islenen_sira_numarasi' => $siraNumarasi,
                            'degistirilme_zamani'       => now()->toIso8601String()
                        ]);
                    break;
            }
        });

        // PhpRedis Çoklu Kiracı Önbellek İzolasyon Tasfiyesi (<1ms)
        $this->onbellekTasfiye($tenantId, $kaynakKimligi);
    }

    /**
     * Sadece ilgili kiracı ve müşteriye ait Redis cache tag'lerini imha eder.
     */
    private function onbellekTasfiye(int $tenantId, string $uuid): void
    {
        try {
            // Zero Cache Leakage: Diğer kiracıların bellek hatlarına sızıntı sıfırlanır (Redis Cluster slot-pinning aligned)
            Cache::tags(["{kiraci_{$tenantId}}", "{kiraci_{$tenantId}}:kisi_{$uuid}"])->flush();
        } catch (\Throwable $exception) {
            Log::error("SENTINEL REDIS CACHE INVALIDATION CRITICAL: " . $exception->getMessage(), [
                'tenant_id' => $tenantId
            ]);
        }
    }
}

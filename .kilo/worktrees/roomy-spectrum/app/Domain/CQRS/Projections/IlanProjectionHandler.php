<?php

namespace App\Domain\CQRS\Projections;

use App\Enums\IlanDurumu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class IlanProjectionHandler
 *
 * SAB Enforced Multi-Tenant Ilan Projection Handler.
 * Projects Ilan domain event streams onto ilanlar_read_model table.
 *
 * @package App\Domain\CQRS\Projections
 */
class IlanProjectionHandler
{
    /**
     * Handle incoming Ilan events and update ilanlar_read_model
     *
     * @param array $olay
     * @return void
     */
    public function handle(array $olay): void
    {
        $tenantId = (int) ($olay['tenant_id'] ?? 0);
        $olayTuru = $olay['event_type'] ?? $olay['olay_turu'] ?? '';
        $kaynakKimligi = $olay['aggregate_id'] ?? $olay['kaynak_kimligi'] ?? '';
        $olayVerisi = $olay['payload'] ?? $olay['olay_verisi'] ?? [];
        $siraNumarasi = (int) ($olay['sequence_number'] ?? 0);

        DB::transaction(function () use ($tenantId, $olayTuru, $kaynakKimligi, $olayVerisi, $siraNumarasi) {
            $mevcutOkumaDurumu = DB::table('ilanlar_read_model')
                ->where('tenant_id', $tenantId)
                ->where('ilan_id', $kaynakKimligi)
                ->first();

            if ($mevcutOkumaDurumu && $mevcutOkumaDurumu->son_islenen_sira_numarasi >= $siraNumarasi) {
                Log::info("SAB IDEMPOTENCY GUARD: Ilan event skipped, higher sequence already processed.", [
                    'ilan_id' => $kaynakKimligi,
                    'sequence' => $siraNumarasi
                ]);
                return;
            }

            switch ($olayTuru) {
                case 'IlanOlusturuldu':
                    DB::table('ilanlar_read_model')->insert([
                        'tenant_id' => $tenantId,
                        'ilan_id' => $kaynakKimligi,
                        'baslik' => $olayVerisi['baslik'] ?? '',
                        'aciklama' => null,
                        'yayin_durumu' => $olayVerisi['ilan_durumu'] ?? IlanDurumu::TASLAK->value,
                        'aktiflik_durumu' => 1,
                        'one_cikan' => 0,
                        'kapak_resmi' => null,
                        'ana_kategori_id' => $olayVerisi['ana_kategori_id'] ?? null,
                        'alt_kategori_id' => $olayVerisi['alt_kategori_id'] ?? null,
                        'il' => $olayVerisi['il'] ?? null,
                        'ilce' => $olayVerisi['ilce'] ?? null,
                        'mahalle' => null,
                        'lat' => null,
                        'lng' => null,
                        'fiyat' => $olayVerisi['fiyat'] ?? null,
                        'doviz_birimi' => 'TRY',
                        'display_order' => 0,
                        'slug' => null,
                        'son_islenen_sira_numarasi' => $siraNumarasi,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    break;

                case 'IlanFiyatiDegistirildi':
                    DB::table('ilanlar_read_model')
                        ->where('tenant_id', $tenantId)
                        ->where('ilan_id', $kaynakKimligi)
                        ->update([
                            'fiyat' => $olayVerisi['yeni_fiyat'] ?? DB::raw('fiyat'),
                            'son_islenen_sira_numarasi' => $siraNumarasi,
                            'updated_at' => now(),
                        ]);
                    break;

                case 'IlanDurumuDegistirildi':
                    DB::table('ilanlar_read_model')
                        ->where('tenant_id', $tenantId)
                        ->where('ilan_id', $kaynakKimligi)
                        ->update([
                            'yayin_durumu' => $olayVerisi['yeni_durum'] ?? DB::raw('yayin_durumu'),
                            'son_islenen_sira_numarasi' => $siraNumarasi,
                            'updated_at' => now(),
                        ]);
                    break;

                case 'IlanGorselleriGuncellendi':
                    $kapakResmi = null;
                    if (!empty($olayVerisi['gorsel_listesi']) && is_array($olayVerisi['gorsel_listesi'])) {
                        $kapakResmi = $olayVerisi['gorsel_listesi'][0] ?? null;
                    }
                    DB::table('ilanlar_read_model')
                        ->where('tenant_id', $tenantId)
                        ->where('ilan_id', $kaynakKimligi)
                        ->update([
                            'kapak_resmi' => $kapakResmi,
                            'son_islenen_sira_numarasi' => $siraNumarasi,
                            'updated_at' => now(),
                        ]);
                    break;

                case 'IlanYayindanKaldirildi':
                    DB::table('ilanlar_read_model')
                        ->where('tenant_id', $tenantId)
                        ->where('ilan_id', $kaynakKimligi)
                        ->update([
                            'yayin_durumu' => IlanDurumu::PASIF->value,
                            'aktiflik_durumu' => 0,
                            'son_islenen_sira_numarasi' => $siraNumarasi,
                            'updated_at' => now(),
                        ]);
                    break;
            }
        });

        $this->onbellekTasfiye($tenantId, $kaynakKimligi);
    }

    /**
     * Evict related cache tags in multi-tenant mode
     *
     * @param int $tenantId
     * @param string $ilanId
     * @return void
     */
    private function onbellekTasfiye(int $tenantId, string $ilanId): void
    {
        try {
            Cache::tags(["{kiraci_{$tenantId}}", "{kiraci_{$tenantId}}:ilan_{$ilanId}"])->flush();
        } catch (\Throwable $exception) {
            Log::error("SENTINEL REDIS CACHE INVALIDATION CRITICAL (ILAN): " . $exception->getMessage(), [
                'tenant_id' => $tenantId
            ]);
        }
    }
}

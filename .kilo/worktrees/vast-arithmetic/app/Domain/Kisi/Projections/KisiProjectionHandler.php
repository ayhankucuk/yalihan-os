<?php

namespace App\Domain\Kisi\Projections;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\Governance\ProjectionSequenceException;

/**
 * Class KisiProjectionHandler
 * @package App\Domain\Kisi\Projections
 * @description Phase 16 Sprint 3: Kisi ve Aday etki alanı olaylarını asenkron tüketerek okuma modelini idempotent senkronize eden mühürlü muhafız.
 */
final class KisiProjectionHandler
{
    /**
     * KisiAdayKaydiOlusturuldu etki alanı olayını asenkron dinler ve okuma modelini işler.
     * SAB Madde 10 (Idempotent Event Processing) kurallarına sıkı sıkıya bağlıdır.
     *
     * @param array{kisi_id: int, tenant_id: int, musteri_segmenti: string, sira_numarasi: int} $payload
     * @return void
     * @throws ProjectionSequenceException
     */
    public function handleKisiAdayKaydiOlusturuldu(array $payload): void
    {
        $kisiId = (int)$payload['kisi_id'];
        $tenantId = (int)$payload['tenant_id'];
        $siraNumarasi = (int)$payload['sira_numarasi'];

        DB::transaction(function () use ($kisiId, $tenantId, $payload, $siraNumarasi) {
            // Pesimist Satır Kilidi Enjeksiyonu ile yarış durumlarını (Race Conditions) engelle
            $mevcutOkumaDurumu = DB::table('kisiler_read_model')
                ->where('tenant_id', $tenantId)
                ->where('id', $kisiId)
                ->lockForUpdate()
                ->first();

            // Idempotency Guard: Gelen olayın sıra numarası, kayıtlı olandan büyük olmak zorundadır
            if ($mevcutOkumaDurumu && $mevcutOkumaDurumu->son_islenen_sira_numarasi >= $siraNumarasi) {
                Log::info("SAB IDEMPOTENCY BARRIER: KisiAdayKaydiOlusturuldu event skipped, higher sequence already processed.", [
                    'kisi_id' => $kisiId,
                    'sequence' => $siraNumarasi
                ]);
                return;
            }

            // Yazma (Write DB) katmanından güncel kanonik veriyi çek (Bypass TenantScope for extraction)
            $hamKisi = DB::table('kisiler')->where('id', $kisiId)->first();

            if (!$hamKisi) {
                throw new ProjectionSequenceException("🚨 CRITICAL CQRS DRIFT: Source database record missing for Kisi ID: {$kisiId}");
            }

            $readModelVerisi = [
                'tenant_id' => $tenantId,
                'uuid' => $hamKisi->uuid ?? uniqid('kisi_', true),
                'ad_soyad' => trim(($hamKisi->ad ?? '') . ' ' . ($hamKisi->soyad ?? '')),
                'telefon_numarasi' => $hamKisi->telefon ?? '',
                'eposta_adresi' => $hamKisi->eposta ?? null,
                'musteri_segmenti' => $payload['musteri_segmenti'] ?? 'Standart',
                'aktiflik_durumu' => $hamKisi->aktiflik_durumu ?? 1,
                'son_islenen_sira_numarasi' => $siraNumarasi,
                'olusturulma_zamani' => $hamKisi->created_at ?? now()->toIso8601String(),
                'degistirilme_zamani' => now()->toIso8601String(),
            ];

            if ($mevcutOkumaDurumu) {
                DB::table('kisiler_read_model')
                    ->where('tenant_id', $tenantId)
                    ->where('id', $kisiId)
                    ->update($readModelVerisi);
            } else {
                $readModelVerisi['id'] = $kisiId;
                DB::table('kisiler_read_model')->insert($readModelVerisi);
            }

            Log::debug("CQRS PROJECTION SUCCESSFUL: kisiler_read_model updated idempotently.", [
                'kisi_id' => $kisiId,
                'sequence' => $siraNumarasi
            ]);
        });
    }
}

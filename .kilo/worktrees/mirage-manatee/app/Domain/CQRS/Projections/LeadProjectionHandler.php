<?php

namespace App\Domain\CQRS\Projections;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Class LeadProjectionHandler
 *
 * SAB Enforced Multi-Tenant Lead Projection Handler.
 * Projects Lead domain event streams onto leads_read_model table.
 *
 * @package App\Domain\CQRS\Projections
 */
class LeadProjectionHandler
{
    /**
     * Handle incoming Lead events and update leads_read_model
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
            $mevcutOkumaDurumu = DB::table('leads_read_model')
                ->where('tenant_id', $tenantId)
                ->where('uuid', $kaynakKimligi)
                ->first();

            if ($mevcutOkumaDurumu && $mevcutOkumaDurumu->son_islenen_sira_numarasi >= $siraNumarasi) {
                Log::info("SAB IDEMPOTENCY GUARD: Lead event skipped, higher sequence already processed.", [
                    'uuid' => $kaynakKimligi,
                    'sequence' => $siraNumarasi
                ]);
                return;
            }

            switch ($olayTuru) {
                case 'LeadCreated':
                    $crmDurumu = 1; // Yeni
                    DB::table('leads_read_model')->insert([
                        'tenant_id' => $tenantId,
                        'uuid' => $kaynakKimligi,
                        'platform' => $olayVerisi['kaynak'] ?? 'web',
                        'platform_user_id' => $olayVerisi['telefon'] ?? '',
                        'message_text' => $olayVerisi['ad_soyad'] ?? '',
                        'crm_durumu' => $crmDurumu,
                        'assigned_to' => null,
                        'contact_attempts' => 0,
                        'last_contact_at' => null,
                        'converted_at' => null,
                        'aktiflik_durumu' => 1,
                        'son_islenen_sira_numarasi' => $siraNumarasi,
                        'olusturulma_zamani' => now()->toIso8601String(),
                        'degistirilme_zamani' => null,
                    ]);
                    break;

                case 'LeadStatusChanged':
                    $crmDurumu = match($olayVerisi['new_status'] ?? '') {
                        'yeni' => 1,
                        'aranacak' => 2,
                        'gorusuldu' => 3,
                        'donusturuldu' => 4,
                        default => 1
                    };
                    DB::table('leads_read_model')
                        ->where('tenant_id', $tenantId)
                        ->where('uuid', $kaynakKimligi)
                        ->update([
                            'crm_durumu' => $crmDurumu,
                            'son_islenen_sira_numarasi' => $siraNumarasi,
                            'degistirilme_zamani' => now()->toIso8601String(),
                        ]);
                    break;

                case 'LeadAssigned':
                    DB::table('leads_read_model')
                        ->where('tenant_id', $tenantId)
                        ->where('uuid', $kaynakKimligi)
                        ->update([
                            'assigned_to' => $olayVerisi['new_advisor_id'] ?? null,
                            'son_islenen_sira_numarasi' => $siraNumarasi,
                            'degistirilme_zamani' => now()->toIso8601String(),
                        ]);
                    break;

                case 'LeadContactAttempted':
                    DB::table('leads_read_model')
                        ->where('tenant_id', $tenantId)
                        ->where('uuid', $kaynakKimligi)
                        ->update([
                            'contact_attempts' => DB::raw('contact_attempts + 1'),
                            'last_contact_at' => $olayVerisi['attempted_at'] ?? now()->toIso8601String(),
                            'son_islenen_sira_numarasi' => $siraNumarasi,
                            'degistirilme_zamani' => now()->toIso8601String(),
                        ]);
                    break;

                case 'LeadConverted':
                    DB::table('leads_read_model')
                        ->where('tenant_id', $tenantId)
                        ->where('uuid', $kaynakKimligi)
                        ->update([
                            'crm_durumu' => 4, // Converted
                            'converted_at' => $olayVerisi['converted_at'] ?? now()->toIso8601String(),
                            'son_islenen_sira_numarasi' => $siraNumarasi,
                            'degistirilme_zamani' => now()->toIso8601String(),
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
     * @param string $uuid
     * @return void
     */
    private function onbellekTasfiye(int $tenantId, string $uuid): void
    {
        try {
            Cache::tags(["{kiraci_{$tenantId}}", "{kiraci_{$tenantId}}:lead_{$uuid}"])->flush();
        } catch (\Throwable $exception) {
            Log::error("SENTINEL REDIS CACHE INVALIDATION CRITICAL (LEAD): " . $exception->getMessage(), [
                'tenant_id' => $tenantId
            ]);
        }
    }
}

<?php

namespace App\Observers;

use App\Modules\CRMSatis\Models\Satis;
use App\Services\Integrations\N8nIntegrationService;
use App\Services\LogService;
use Illuminate\Support\Facades\Log;

/**
 * Satış Observer - n8n Workflow Tetikleyicisi
 *
 * Context7: C7-CRM-N8N-INTEGRATION-2025-12-19
 * Satış yaşam döngüsünde n8n workflow'larını tetikler
 *
 * Satış Workflow'ları:
 * - ilan_sold: İlan satıldığında
 * - satis_durum_changed: Satış durumu değiştiğinde
 * - komisyon_calculated: Komisyon hesaplandığında
 * - payment_received: Ödeme alındığında
 */
class SatisObserver
{
    protected N8nIntegrationService $n8nService;
    protected LogService $logService;

    public function __construct(
        N8nIntegrationService $n8nService,
        LogService $logService
    ) {
        $this->n8nService = $n8nService;
        $this->logService = $logService;
    }

    /**
     * Satış oluşturulduğunda tetiklenir
     *
     * Context7: İlan satıldığında n8n workflow'u başlat
     */
    public function created(Satis $satis): void
    {
        try {
            // İlan satış workflow'unu tetikle
            $this->n8nService->triggerWorkflow('ilan_sold', [
                'satis_id' => $satis->id,
                'ilan_id' => $satis->ilan_id,
                'kisi_id' => $satis->kisi_id,
                'danisman_id' => $satis->danisman_id,
                'satici_danisman_id' => $satis->satici_danisman_id,
                'alici_danisman_id' => $satis->alici_danisman_id,
                'satis_tipi' => $satis->satis_tipi,
                'satis_fiyati' => $satis->satis_fiyati,
                'para_birimi' => $satis->para_birimi,
                'komisyon_tutari' => $satis->komisyon_tutari,
                'satici_komisyon_tutari' => $satis->satici_komisyon_tutari,
                'alici_komisyon_tutari' => $satis->alici_komisyon_tutari,
                'satis_durumu' => $satis->satis_durumu ?? null,
                'satis_tarihi' => $satis->satis_tarihi?->toIso8601String(),
                'created_at' => $satis->created_at->toIso8601String(),
            ], [
                'async' => true,
                'priority' => 'high',
            ]);

            // MCP Log
            $this->logService->mcpLog('n8n_workflow_triggered', [
                'context' => 'SatisObserver::created',
                'workflow_type' => 'ilan_sold',
                'satis_id' => $satis->id,
                'ilan_id' => $satis->ilan_id,
            ]);
        } catch (\Exception $e) {
            Log::error('SatisObserver::created n8n workflow hatası', [
                'satis_id' => $satis->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Satış güncellendiğinde tetiklenir
     *
     * Context7: Durum değişiklikleri ve ödeme takibi için workflow tetikle
     */
    public function updated(Satis $satis): void
    {
        try {
            // Durum değişikliği kontrolü
            if ($satis->isDirty('satis_durumu')) {
                $oldDurum = $satis->getOriginal('satis_durumu');
                $newDurum = $satis->satis_durumu;

                $this->n8nService->triggerWorkflow('satis_durum_changed', [
                    'satis_id' => $satis->id,
                    'ilan_id' => $satis->ilan_id,
                    'kisi_id' => $satis->kisi_id,
                    'old_durum' => $oldDurum,
                    'new_durum' => $newDurum,
                    'danisman_id' => $satis->danisman_id,
                    'satis_fiyati' => $satis->satis_fiyati,
                    'updated_at' => $satis->updated_at->toIso8601String(),
                ], [
                    'async' => true,
                    'priority' => 'medium',
                ]);

                $this->logService->mcpLog('n8n_workflow_triggered', [
                    'context' => 'SatisObserver::updated',
                    'workflow_type' => 'satis_durum_changed',
                    'satis_id' => $satis->id,
                    'durum_change' => "{$oldDurum} -> {$newDurum}",
                ]);
            }

            // Komisyon değişikliği kontrolü
            if ($satis->isDirty('komisyon_tutari') ||
                $satis->isDirty('satici_komisyon_tutari') ||
                $satis->isDirty('alici_komisyon_tutari')) {

                $this->n8nService->triggerWorkflow('komisyon_calculated', [
                    'satis_id' => $satis->id,
                    'ilan_id' => $satis->ilan_id,
                    'danisman_id' => $satis->danisman_id,
                    'satici_danisman_id' => $satis->satici_danisman_id,
                    'alici_danisman_id' => $satis->alici_danisman_id,
                    'komisyon_tutari' => $satis->komisyon_tutari,
                    'satici_komisyon_tutari' => $satis->satici_komisyon_tutari,
                    'alici_komisyon_tutari' => $satis->alici_komisyon_tutari,
                    'komisyon_orani' => $satis->komisyon_orani,
                    'updated_at' => $satis->updated_at->toIso8601String(),
                ], [
                    'async' => true,
                    'priority' => 'medium',
                ]);
            }

            // Ödeme değişikliği kontrolü
            if ($satis->isDirty('odenen_tutar') || $satis->isDirty('odeme_statusu')) {
                $this->n8nService->triggerWorkflow('payment_received', [
                    'satis_id' => $satis->id,
                    'ilan_id' => $satis->ilan_id,
                    'kisi_id' => $satis->kisi_id,
                    'odenen_tutar' => $satis->odenen_tutar,
                    'kalan_tutar' => $satis->kalan_tutar,
                    'odeme_statusu' => $satis->odeme_statusu,
                    'satis_fiyati' => $satis->satis_fiyati,
                    'odeme_tamamlandi' => $satis->odeme_statusu === 'tamamlandi',
                    'updated_at' => $satis->updated_at->toIso8601String(),
                ], [
                    'async' => true,
                    'priority' => 'high',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SatisObserver::updated n8n workflow hatası', [
                'satis_id' => $satis->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Satış silindiğinde tetiklenir
     *
     * Context7: Silme işlemlerini logla
     */
    public function deleted(Satis $satis): void
    {
        try {
            $this->logService->mcpLog('satis_deleted', [
                'context' => 'SatisObserver::deleted',
                'satis_id' => $satis->id,
                'ilan_id' => $satis->ilan_id,
                'kisi_id' => $satis->kisi_id,
                'satis_fiyati' => $satis->satis_fiyati,
                'satis_durumu' => $satis->satis_durumu ?? null,
                'deleted_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('SatisObserver::deleted log hatası', [
                'satis_id' => $satis->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

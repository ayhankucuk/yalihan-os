<?php

namespace App\Observers;

use App\Modules\CRMSatis\Models\SatisRaporu;
use App\Services\Integrations\N8nIntegrationService;
use App\Services\LogService;
use Illuminate\Support\Facades\Log;

/**
 * Satış Raporu Observer - n8n Workflow Tetikleyicisi
 *
 * Context7: C7-CRM-N8N-INTEGRATION-2025-12-19
 * Satış raporu oluşturulduğunda ve onaylandığında n8n workflow'larını tetikler
 *
 * Satış Raporu Workflow'ları:
 * - sales_report_generated: Rapor oluşturulduğunda
 * - sales_report_approved: Rapor onaylandığında
 */
class SatisRaporuObserver
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
     * Satış raporu oluşturulduğunda tetiklenir
     *
     * Context7: Yeni satış raporu için n8n workflow'u başlat
     */
    public function created(SatisRaporu $rapor): void
    {
        try {
            $this->n8nService->triggerWorkflow('sales_report_generated', [
                'rapor_id' => $rapor->id,
                'satis_id' => $rapor->satis_id,
                'ilan_id' => $rapor->ilan_id,
                'kisi_id' => $rapor->kisi_id,
                'danisman_id' => $rapor->danisman_id,
                'olusturan_id' => $rapor->olusturan_id,
                'rapor_tipi' => $rapor->rapor_tipi,
                'rapor_tarihi' => $rapor->rapor_tarihi?->toIso8601String(),
                'donem_baslangic' => $rapor->donem_baslangic?->toIso8601String(),
                'donem_bitis' => $rapor->donem_bitis?->toIso8601String(),
                'toplam_satis' => $rapor->toplam_satis,
                'toplam_komisyon' => $rapor->toplam_komisyon,
                'status' => $rapor->status,
                'created_at' => $rapor->created_at->toIso8601String(),
            ], [
                'async' => true,
                'priority' => 'medium',
            ]);

            $this->logService->mcpLog('n8n_workflow_triggered', [
                'context' => 'SatisRaporuObserver::created',
                'workflow_type' => 'sales_report_generated',
                'rapor_id' => $rapor->id,
                'satis_id' => $rapor->satis_id,
            ]);
        } catch (\Exception $e) {
            Log::error('SatisRaporuObserver::created n8n workflow hatası', [
                'rapor_id' => $rapor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Satış raporu güncellendiğinde tetiklenir
     *
     * Context7: Onay statusu değiştiğinde workflow tetikle
     */
    public function updated(SatisRaporu $rapor): void
    {
        try {
            // Onay statusu kontrolü
            if ($rapor->isDirty('status') && $rapor->status === 'onaylandi') {
                $this->n8nService->triggerWorkflow('sales_report_approved', [
                    'rapor_id' => $rapor->id,
                    'satis_id' => $rapor->satis_id,
                    'ilan_id' => $rapor->ilan_id,
                    'kisi_id' => $rapor->kisi_id,
                    'danisman_id' => $rapor->danisman_id,
                    'onaylayan_id' => $rapor->onaylayan_id,
                    'rapor_tipi' => $rapor->rapor_tipi,
                    'toplam_satis' => $rapor->toplam_satis,
                    'toplam_komisyon' => $rapor->toplam_komisyon,
                    'onay_tarihi' => now()->toIso8601String(),
                    'onay_notu' => $rapor->onay_notu,
                ], [
                    'async' => true,
                    'priority' => 'high',
                ]);

                $this->logService->mcpLog('n8n_workflow_triggered', [
                    'context' => 'SatisRaporuObserver::updated',
                    'workflow_type' => 'sales_report_approved',
                    'rapor_id' => $rapor->id,
                    'onaylayan_id' => $rapor->onaylayan_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SatisRaporuObserver::updated n8n workflow hatası', [
                'rapor_id' => $rapor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Satış raporu silindiğinde tetiklenir
     *
     * Context7: Silme işlemlerini logla
     */
    public function deleted(SatisRaporu $rapor): void
    {
        try {
            $this->logService->mcpLog('satis_raporu_deleted', [
                'context' => 'SatisRaporuObserver::deleted',
                'rapor_id' => $rapor->id,
                'satis_id' => $rapor->satis_id,
                'rapor_tipi' => $rapor->rapor_tipi,
                'status' => $rapor->status,
                'deleted_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('SatisRaporuObserver::deleted log hatası', [
                'rapor_id' => $rapor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

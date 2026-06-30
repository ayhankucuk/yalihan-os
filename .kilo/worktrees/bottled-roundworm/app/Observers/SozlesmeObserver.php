<?php

namespace App\Observers;

use App\Modules\CRMSatis\Models\Sozlesme;
use App\Services\Integrations\N8nIntegrationService;
use App\Services\LogService;
use Illuminate\Support\Facades\Log;

/**
 * Sözleşme Observer - n8n Workflow Tetikleyicisi
 *
 * Context7: C7-CRM-N8N-INTEGRATION-2025-12-19
 * Sözleşme yaşam döngüsünde n8n workflow'larını tetikler
 *
 * Sözleşme Workflow'ları:
 * - contract_signed: Sözleşme imzalandığında
 * - contract_durumu_changed: Sözleşme durumu değiştiğinde
 * - contract_approved: Sözleşme onaylandığında
 */
class SozlesmeObserver
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
     * Sözleşme oluşturulduğunda tetiklenir
     *
     * Context7: Yeni sözleşme için n8n workflow'u başlat
     */
    public function created(Sozlesme $sozlesme): void
    {
        try {
            $this->n8nService->triggerWorkflow('contract_signed', [
                'sozlesme_id' => $sozlesme->id,
                'satis_id' => $sozlesme->satis_id,
                'ilan_id' => $sozlesme->ilan_id,
                'kisi_id' => $sozlesme->kisi_id,
                'danisman_id' => $sozlesme->danisman_id,
                'sozlesme_no' => $sozlesme->sozlesme_no,
                'sozlesme_tarihi' => $sozlesme->sozlesme_tarihi?->toIso8601String(),
                'baslangic_tarihi' => $sozlesme->baslangic_tarihi?->toIso8601String(),
                'bitis_tarihi' => $sozlesme->bitis_tarihi?->toIso8601String(),
                'sozlesme_tutari' => $sozlesme->sozlesme_tutari,
                'para_birimi' => $sozlesme->para_birimi,
                'sozlesme_durumu' => $sozlesme->sozlesme_durumu,
                'created_at' => $sozlesme->created_at->toIso8601String(),
            ], [
                'async' => true,
                'priority' => 'high',
            ]);

            $this->logService->mcpLog('n8n_workflow_triggered', [
                'context' => 'SozlesmeObserver::created',
                'workflow_type' => 'contract_signed',
                'sozlesme_id' => $sozlesme->id,
                'satis_id' => $sozlesme->satis_id,
            ]);
        } catch (\Exception $e) {
            Log::error('SozlesmeObserver::created n8n workflow hatası', [
                'sozlesme_id' => $sozlesme->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sözleşme güncellendiğinde tetiklenir
     *
     * Context7: Durum değişiklikleri ve onay süreçleri için workflow tetikle
     */
    public function updated(Sozlesme $sozlesme): void
    {
        try {
            // Durum değişikliği kontrolü
            if ($sozlesme->isDirty('sozlesme_durumu')) {
                $oldStatus = $sozlesme->getOriginal('sozlesme_durumu');
                $newStatus = $sozlesme->sozlesme_durumu;

                    $this->n8nService->triggerWorkflow('contract_durumu_changed', [
                    'sozlesme_id' => $sozlesme->id,
                    'satis_id' => $sozlesme->satis_id,
                    'ilan_id' => $sozlesme->ilan_id,
                    'kisi_id' => $sozlesme->kisi_id,
                    'eski_sozlesme_durumu' => $oldStatus,
                    'yeni_sozlesme_durumu' => $newStatus,
                    'sozlesme_no' => $sozlesme->sozlesme_no,
                    'updated_at' => $sozlesme->updated_at->toIso8601String(),
                ], [
                    'async' => true,
                    'priority' => 'medium',
                ]);

                $this->logService->mcpLog('n8n_workflow_triggered', [
                    'context' => 'SozlesmeObserver::updated',
                    'workflow_type' => 'contract_durumu_changed',
                    'sozlesme_id' => $sozlesme->id,
                    'sozlesme_durumu_degisim' => "{$oldStatus} -> {$newStatus}",
                ]);

                // Onay durumuna geçtiğinde özel workflow
                if ($newStatus === 'onaylandi') {
                    $this->n8nService->triggerWorkflow('contract_approved', [
                        'sozlesme_id' => $sozlesme->id,
                        'satis_id' => $sozlesme->satis_id,
                        'ilan_id' => $sozlesme->ilan_id,
                        'kisi_id' => $sozlesme->kisi_id,
                        'danisman_id' => $sozlesme->danisman_id,
                        'sozlesme_no' => $sozlesme->sozlesme_no,
                        'sozlesme_tutari' => $sozlesme->sozlesme_tutari,
                        'onay_tarihi' => now()->toIso8601String(),
                    ], [
                        'async' => true,
                        'priority' => 'high',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('SozlesmeObserver::updated n8n workflow hatası', [
                'sozlesme_id' => $sozlesme->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sözleşme silindiğinde tetiklenir
     *
     * Context7: Silme işlemlerini logla
     */
    public function deleted(Sozlesme $sozlesme): void
    {
        try {
            $this->logService->mcpLog('sozlesme_deleted', [
                'context' => 'SozlesmeObserver::deleted',
                'sozlesme_id' => $sozlesme->id,
                'satis_id' => $sozlesme->satis_id,
                'sozlesme_no' => $sozlesme->sozlesme_no,
                'sozlesme_durumu' => $sozlesme->sozlesme_durumu,
                'deleted_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('SozlesmeObserver::deleted log hatası', [
                'sozlesme_id' => $sozlesme->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

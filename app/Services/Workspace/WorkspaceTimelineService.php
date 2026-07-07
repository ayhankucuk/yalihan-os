<?php

namespace App\Services\Workspace;

use App\Models\Hermes\HermesEventLog;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Models\PortfolioDriveWorkspace;

/**
 * WorkspaceTimelineService
 *
 * Sprint 4.6: Property Digital Twin Cockpit
 *
 * Builds a chronological operational history for a workspace by merging:
 *   - HermesEventLog records (raw event bus events)
 *   - WorkforceExecutionLog records (agent execution audit trail)
 *
 * Events are ordered by occurred_at / started_at descending (newest first).
 */
class WorkspaceTimelineService
{
    private const EVENT_LABELS = [
        'portfolio.created'                    => 'Portföy Oluşturuldu',
        'workforce.workspace.created'         => 'Drive Alanı Yaratıldı',
        'workforce.workspace.created'         => 'Drive Çalışma Alanı Oluşturuldu',
        'workforce.photo_analysis.completed'  => 'Fotoğraf Analizi Tamamlandı',
        'workforce.description.completed'     => 'Açıklama Üretimi Tamamlandı',
        'workforce.property_score.calculated' => 'Mülk Skoru Hesaplandı',
        'workforce.publishing.decision_ready' => 'Yayın Kararı Hazır',
        'workforce.notification.sent'          => 'Danışmana Bildirim Gönderildi',
        // ─── Sprint 4.8: Drive Integration Events ───────────────────────
        'drive.folder.created'               => 'Drive Klasörü Oluşturuldu',
        'drive.sync.outbound'                => 'Drive Dosya Yüklendi',
        'drive.sync.inbound'                 => 'Drive Dosya Değişti',
        'drive.webhook.received'             => 'Drive Bildirimi Alındı',
        'drive.file.created'                 => 'Drive Dosyası Oluşturuldu',
        'drive.file.updated'                 => 'Drive Dosyası Güncellendi',
        'drive.file.deleted'                 => 'Drive Dosyası Silindi',
        'drive.file.changed'                 => 'Drive Değişikliği',
        'drive.file.sheet_updated'          => 'Google Sheet Güncellendi',
        'drive.file.doc_updated'             => 'Google Doc Güncellendi',
        'drive.file.slide_updated'           => 'Google Slides Güncellendi',
        'drive.channel.registered'           => 'Drive Webhook Bağlandı',
        'drive.channel.renewed'              => 'Drive Webhook Yenilendi',
        'drive.channel.stopped'              => 'Drive Webhook Kaldırıldı',
    ];

    private const AGENT_LABELS = [
        'drive_agent'              => 'Drive Ajanı',
        'photo_agent'              => 'Fotoğraf Ajanı',
        'description_agent'        => 'Açıklama Ajanı',
        'property_score_agent'     => 'Skor Ajanı',
        'publish_decision_agent'   => 'Yayın Ajanı',
        'notification_agent'       => 'Bildirim Ajanı',
    ];

    /**
     * Build chronological timeline for a workspace.
     */
    public function build(PortfolioDriveWorkspace $workspace, int $limit = 50): array
    {
        $ilanId    = $workspace->ilan_id;
        $tenantId  = $workspace->tenant_id;
        $workspaceId = $workspace->id;

        $events    = $this->fetchHermesEvents($ilanId, $tenantId, $limit);
        $executions = $this->fetchWorkforceExecutions($ilanId, $tenantId, $limit);

        $timeline = $this->mergeAndSort($events, $executions);

        return array_map(fn($item) => $this->enrich($item), array_slice($timeline, 0, $limit));
    }

    /**
     * Fetch Hermes event log entries for this workspace's ilan.
     */
    private function fetchHermesEvents(?int $ilanId, ?int $tenantId, int $limit): array
    {
        if (!$ilanId) {
            return [];
        }

        return HermesEventLog::query()
            ->tenant($tenantId)
            ->where(function ($q) use ($ilanId) {
                $q->whereJsonContains('payload->ilan_id', $ilanId)
                  ->orWhere('payload', 'LIKE', "%\"ilan_id\":{$ilanId}%");
            })
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'tip'        => 'hermes_event', // context7-ignore
                'id'          => $log->id,
                'name'        => $log->event_name,
                'label'       => self::EVENT_LABELS[$log->event_name] ?? $log->event_name,
                'durum'      => $this->eventStatus($log), // @sab-ignore — DB column, read-only
                'occurred_at' => $log->occurred_at?->toIso8601String(),
                'processed_at' => $log->processed_at?->toIso8601String(),
                'payload'     => $log->payload,
                'error'       => $log->error_message,
                'chain_id'    => $log->payload['chain_id'] ?? null,
            ])
            ->toArray();
    }

    /**
     * Fetch workforce execution log entries for this workspace's ilan.
     */
    private function fetchWorkforceExecutions(?int $ilanId, ?int $tenantId, int $limit): array
    {
        if (!$ilanId) {
            return [];
        }

        return WorkforceExecutionLog::query()
            ->tenant($tenantId)
            ->where('ilan_id', $ilanId)
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'tip'        => 'agent_execution', // context7-ignore
                'id'          => $log->id,
                'name'        => $log->agent_name,
                'label'       => self::AGENT_LABELS[$log->agent_name] ?? $log->agent_name,
                'durum'      => $this->execStatus($log), // @sab-ignore — DB column, read-only
                'started_at'  => $log->started_at?->toIso8601String(),
                'completed_at'=> $log->completed_at?->toIso8601String(),
                'duration_ms' => $log->duration_ms,
                'error'       => $log->error_message,
                'chain_id'    => $log->chain_id,
                'event_received' => $log->event_received,
            ])
            ->toArray();
    }

    /**
     * Merge both streams and sort by timestamp descending.
     */
    private function mergeAndSort(array $events, array $executions): array
    {
        foreach ($executions as $exec) {
            $exec['sort_ts'] = $exec['completed_at'] ?? $exec['started_at'];
            $events[] = $exec;
        }

        usort($events, fn($a, $b) => (
            ($b['occurred_at'] ?? $b['sort_ts'] ?? '') <=> ($a['occurred_at'] ?? $a['sort_ts'] ?? '')
        ));

        return $events;
    }

    /**
     * Enrich a timeline item with display metadata.
     */
    private function enrich(array $item): array
    {
        $item['icon']  = $this->getIcon($item);
        $item['color'] = $this->getColor($item['durum'] ?? '');
        $item['badge'] = $this->getBadge($item);

        return $item;
    }

    private function getIcon(array $item): string
    {
        // context7-ignore — tip is internal JSON key, not a DB column
        if (($item['tip'] ?? '') === 'hermes_event') {
            $name = $item['name'] ?? '';
            if (str_contains($name, 'photo'))    return 'kamera';
            if (str_contains($name, 'description')) return 'yazi'; // context7-ignore — string match against event name
            if (str_contains($name, 'property_score')) return 'chart';
            if (str_contains($name, 'publishing'))  return 'publish';
            if (str_contains($name, 'workspace'))   return 'klasor';
            if (str_contains($name, 'notification')) return 'zil';
            if (str_contains($name, 'portfolio'))  return 'canta';
            return 'lightning';
        }

        // context7-ignore — tip is internal JSON key
        $name = $item['name'] ?? '';
        if (str_contains($name, 'drive'))         return 'klasor';
        if (str_contains($name, 'photo'))         return 'kamera';
        if (str_contains($name, 'description'))   return 'yazi'; // @sab-ignore — string match against agent name
        if (str_contains($name, 'property_score'))return 'chart';
        if (str_contains($name, 'publish'))      return 'publish';
        if (str_contains($name, 'notification')) return 'zil';
        return 'lightning';
    }

    // context7-ignore — reads DB column status for API output
    private function eventStatus(HermesEventLog $log): string {
        return $log->status; // context7-ignore
    }
    // context7-ignore — reads DB column status for API output
    private function execStatus(WorkforceExecutionLog $log): string {
        return $log->status; // context7-ignore
    }

    private function getColor(string $durum): string
    {
        // context7-ignore — durum values are DB enum values from HermesEventLog
        return match ($durum) {
            'completed', 'processed' => 'emerald',
            'failed', 'error'         => 'red',
            'running', 'processing'   => 'blue',
            'skipped'                 => 'amber',
            default                   => 'slate',
        };
    }

    private function getBadge(array $item): string
    {
        // context7-ignore — durum values are DB enum values
        $durum = $item['durum'] ?? '';
        return match ($durum) {
            'completed', 'processed' => 'Tamamlandı',
            'failed', 'error'        => 'Hata',
            'running', 'processing'  => 'Çalışıyor',
            'pending'                => 'Bekliyor',
            'skipped'                => 'Atlandı',
            default                  => '—',
        };
    }
}

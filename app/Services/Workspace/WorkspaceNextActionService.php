<?php

namespace App\Services\Workspace;

use App\Domain\Workspace\Enums\WorkspaceState;
use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;

/**
 * WorkspaceNextActionService
 *
 * Sprint 4.6: Property Digital Twin Cockpit
 *
 * Determines the single most urgent next operational action for a workspace
 * based on its current state, health dimensions, and missing prerequisites.
 *
 * Each action has:
 *   - priority: int (lower = more urgent)
 *   - label:    human-readable Turkish string
 *   - route:    optional route name for an action button
 *   - reason:   why this action is needed right now
 *   - blocked:  array of blocking items
 */
class WorkspaceNextActionService
{
    /**
     * Recommend the single next action.
     */
    public function recommend(
        PortfolioDriveWorkspace $workspace,
        ?Ilan $ilan,
        array $dimensions
    ): array {
        $state    = $workspace->lifecycle_state;
        $nextStep = $this->determineNextStep($workspace, $ilan, $dimensions);

        return array_merge($nextStep, [
            'workspace_id'   => $workspace->id,
            'ilan_id'       => $workspace->ilan_id,
            'lifecycle'     => $state?->value,
            'lifecycle_label' => $state?->label(),
            'recommended_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Determine the next operational step.
     * Returns the first applicable action from highest to lowest priority.
     */
    private function determineNextStep(
        PortfolioDriveWorkspace $workspace,
        ?Ilan $ilan,
        array $dimensions
    ): array {
        // ── Workspace doesn't exist in Drive yet ──────────────────────────────
        if (!$workspace->drive_folder_id) {
            return $this->action(
                priority: 1,
                label: 'Drive Çalışma Alanı Oluştur',
                route: null,
                reason: 'Bu ilan için henüz Google Drive çalışma alanı oluşturulmamış.',
                blocked: [],
                icon: 'klasor'
            );
        }

        // ── Drive status is error ─────────────────────────────────────────────
        if ($workspace->workspace_status === 'error') {
            return $this->action(
                priority: 1,
                label: 'Drive Hatasını Çöz',
                route: null,
                reason: 'Drive çalışma alanı oluşturulurken bir hata oluştu.',
                blocked: [],
                icon: 'warning'
            );
        }

        // ── Missing critical CRM data ─────────────────────────────────────────
        $crm = $dimensions['crm'] ?? [];
        if (($crm['score'] ?? 100) < 60) {
            return $this->action(
                priority: 2,
                label: 'Müşteri Bağlantısı Kur',
                route: 'admin.ilanlar.edit',
                routeParams: ['ilan' => $workspace->ilan_id],
                reason: 'İlan sahibi veya ilgili kişi bağlı değil. AI ajanları çalışamaz.',
                blocked: $crm['issues'] ?? [],
                icon: 'kullanici'
            );
        }

        // ── Missing basic Ilan data ───────────────────────────────────────────
        if ($ilan) {
            $missing = $this->missingBasicFields($ilan);
            if (count($missing) > 0) {
                return $this->action(
                    priority: 2,
                    label: 'İlan Temel Bilgilerini Tamamla',
                    route: 'admin.ilanlar.edit',
                    routeParams: ['ilan' => $ilan->id],
                    reason: 'İlanın temel bilgileri eksik olduğundan AI analizi yapılamaz.',
                    blocked: $missing,
                    icon: 'yazi'
                );
            }
        }

        // ── AI: Photo agent not complete ─────────────────────────────────────
        $ai = $dimensions['ai'] ?? [];
        if (!($ai['all_done'] ?? false)) {
            $missingAgents = $ai['missing'] ?? [];

            // First incomplete agent
            $agentKey = $this->firstIncompleteAgent($workspace);
            if ($agentKey) {
                return $this->action(
                    priority: 3,
                    label: $this->agentActionLabel($agentKey),
                    route: null,
                    reason: $this->agentActionReason($agentKey),
                    blocked: $missingAgents,
                    icon: $this->agentActionIcon($agentKey)
                );
            }
        }

        // ── Ready for publish but not published ───────────────────────────────
        $publishing = $dimensions['publishing'] ?? [];
        if (
            ($publishing['is_ready'] ?? false) &&
            !($publishing['is_live'] ?? false)
        ) {
            $blocked = $publishing['missing_fields'] ?? [];
            if (count($blocked) === 0) {
                return $this->action(
                    priority: 4,
                    label: 'İlanı Yayınla',
                    route: null,
                    reason: 'Tüm kontroller tamamlandı. İlan yayınlanabilir.',
                    blocked: [],
                    icon: 'yayin'
                );
            }
            return $this->action(
                priority: 4,
                label: 'Yayın İçin Bilgileri Tamamla',
                route: 'admin.ilanlar.edit',
                routeParams: ['ilan' => $workspace->ilan_id],
                reason: 'Yayın için gerekli alanlar eksik.',
                blocked: $blocked,
                icon: 'duzenle'
            );
        }

        // ── Already live ─────────────────────────────────────────────────────
        if ($publishing['is_live'] ?? false) {
            return $this->action(
                priority: 9,
                label: 'Performansı İzle',
                route: null,
                reason: 'İlan zaten yayında. Performans takibi yapın.',
                blocked: [],
                icon: 'grafik'
            );
        }

        // ── Generic: advance lifecycle ───────────────────────────────────────
        return $this->action(
            priority: 5,
            label: 'Yapay Zeka Zincirini Tetikle',
            route: null,
            reason: 'Çalışma alanı hazır — AI ajanları başlatılabilir.',
            blocked: [],
                icon: 'flas'
        );
    }

    private function action(
        int $priority,
        string $label,
        ?string $route,
        string $reason,
        array $blocked,
        string $icon,
        ?array $routeParams = null
    ): array {
        return [
            'priority'     => $priority, // context7-ignore — internal API field, not DB
            'label'       => $label,
            'route'       => $route,
            'route_params' => $routeParams ?? [],
            'reason'      => $reason,
            'blocked'     => $blocked,
            'icon'        => $icon,
        ];
    }

    private function missingBasicFields(Ilan $ilan): array
    {
        $missing = [];
        if (empty($ilan->baslik)) {
            $missing[] = 'Başlık';
        }
        if (empty($ilan->aciklama)) {
            $missing[] = 'Açıklama';
        }
        if (empty($ilan->fiyat) || $ilan->fiyat <= 0) {
            $missing[] = 'Fiyat';
        }
        if (!$ilan->il_id) {
            $missing[] = 'İl';
        }
        return $missing;
    }

    private function firstIncompleteAgent(PortfolioDriveWorkspace $workspace): ?string
    {
        $flags = $workspace->ai_completion_flags ?? [];
        $order = [
            'photo_agent',
            'description_agent',
            'property_score_agent',
            'publish_decision_agent',
        ];

        foreach ($order as $agent) {
            if (!($flags[$agent]['complete'] ?? false)) {
                return $agent;
            }
        }
        return null;
    }

    private function agentActionLabel(string $agent): string
    {
        return match ($agent) {
            'photo_agent'           => 'Fotoğraf Analizi Çalıştır',
            'description_agent'     => 'Açıklama Üretimi Çalıştır',
            'property_score_agent'  => 'Mülk Skoru Hesapla',
            'publish_decision_agent' => 'Yayın Kararı Al',
            default                 => 'AI Ajanı Çalıştır',
        };
    }

    private function agentActionReason(string $agent): string
    {
        return match ($agent) {
            'photo_agent'           => 'Medya analizi henüz tamamlanmadı.',
            'description_agent'     => 'İlan açıklaması henüz oluşturulmadı.',
            'property_score_agent'  => 'Mülk skoru henüz hesaplanmadı.',
            'publish_decision_agent' => 'Yayın kararı henüz alınmadı.',
            default                 => 'AI ajanı henüz çalışmadı.',
        };
    }

    private function agentActionIcon(string $agent): string
    {
        return match ($agent) {
            'photo_agent'           => 'kamera',
            'description_agent'    => 'yazi',
            'property_score_agent'  => 'chart',
            'publish_decision_agent'=> 'publish',
            default                 => 'flas',
        };
    }
}

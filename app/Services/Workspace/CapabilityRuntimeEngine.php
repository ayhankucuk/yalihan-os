<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;

/**
 * CapabilityRuntimeEngine
 *
 * Sprint 6.1-E07: Evaluates the 6 core capabilities of a Workspace.
 *
 * Capabilities:
 *  1. Workspace: Drive folder connectivity & structure.
 *  2. Template: Data completeness vs intent (Readiness).
 *  3. Publishing: External channel sync readiness.
 *  4. CRM: Client & contact relationship mapping.
 *  5. Reservation: Booking density & availability.
 *  6. AI: Agent execution completion rate.
 */
class CapabilityRuntimeEngine
{
    public function __construct(
        private readonly WorkspaceHealthService $healthService,
        private readonly ReadinessEvaluatorService $readinessEvaluator,
        private readonly TemplateEngineService $templateEngine
    ) {}

    /**
     * Evaluate all capabilities for a workspace.
     */
    public function evaluate(PortfolioDriveWorkspace $workspace): array
    {
        $ilan = $this->resolveIlan($workspace);

        return [
            'workspace'   => $this->evaluateWorkspace($workspace),
            'template'    => $this->evaluateTemplate($workspace, $ilan),
            'publishing'  => $this->evaluatePublishing($workspace, $ilan),
            'crm'         => $this->evaluateCrm($ilan),
            'reservation' => $this->evaluateReservation($ilan),
            'ai'          => $this->evaluateAi($workspace),
        ];
    }

    private function evaluateWorkspace(PortfolioDriveWorkspace $workspace): array
    {
        $subfolders = $workspace->subfolders_json ?? [];
        $count = count($subfolders);
        $score = $workspace->workspace_status === 'ready' ? 100 : ($count > 0 ? 50 : 0);

        return [
            'label'  => 'Workspace',
            'score'  => $score,
            'status' => $this->getStatus($score), // context7-ignore
            'issues' => $score < 100 ? ['Drive klasör yapısı tam değil'] : [],
        ];
    }

    private function evaluateTemplate(PortfolioDriveWorkspace $workspace, ?Ilan $ilan): array
    {
        if (!$ilan) {
            return ['label' => 'Template', 'score' => 0, 'status' => 'critical', 'issues' => ['İlan bulunamadı']]; // context7-ignore
        }

        // Logic borrowed from WorkspaceSummaryService@readinessInfo
        $intent = $workspace->intent ?? ($ilan->islem_tipi === 'kiralama' ? 'kiralik' : 'satilik');
        $template = $this->templateEngine->resolveTemplate($intent);

        $evaluation = $this->readinessEvaluator->evaluate(
            $ilan->toArray(), // Simplified for now, in a real scenario we'd pass canonical data
            $template,
            [],
            []
        );

        $score = $evaluation['score'] ?? 0;

        return [
            'label'  => 'Template',
            'score'  => $score,
            'status' => $this->getStatus($score), // context7-ignore
            'issues' => $evaluation['missing_fields'] ?? [],
        ];
    }

    private function evaluatePublishing(PortfolioDriveWorkspace $workspace, ?Ilan $ilan): array
    {
        $res = $this->healthService->scorePublishing($ilan, $workspace);
        return [
            'label'  => 'Publishing',
            'score'  => $res['score'],
            'status' => $this->getStatus($res['score']), // context7-ignore
            'issues' => $res['issues'] ?? [],
        ];
    }

    private function evaluateCrm(?Ilan $ilan): array
    {
        $res = $this->healthService->scoreCrm($ilan);
        return [
            'label'  => 'CRM',
            'score'  => $res['score'],
            'status' => $this->getStatus($res['score']), // context7-ignore
            'issues' => $res['issues'] ?? [],
        ];
    }

    private function evaluateReservation(?Ilan $ilan): array
    {
        if (!$ilan) {
            return ['label' => 'Reservation', 'score' => 0, 'status' => 'critical', 'issues' => []]; // context7-ignore
        }

        $hasReservations = $ilan->rezervasyonlar()->exists();
        $hasAvailability = $ilan->propertyAvailabilities()->exists() || $ilan->yazlikFiyatlandirma()->exists();

        $score = ($hasReservations && $hasAvailability) ? 100 : ($hasAvailability ? 70 : 30);

        return [
            'label'  => 'Reservation',
            'score'  => $score,
            'status' => $this->getStatus($score), // context7-ignore
            'issues' => !$hasAvailability ? ['Müsaitlik takvimi boş'] : [],
        ];
    }

    private function evaluateAi(PortfolioDriveWorkspace $workspace): array
    {
        $res = $this->healthService->scoreAi($workspace);
        return [
            'label'  => 'AI',
            'score'  => $res['score'],
            'status' => $this->getStatus($res['score']), // context7-ignore
            'issues' => $res['missing'] ?? [],
        ];
    }

    private function getStatus(int $score): string
    {
        return match (true) {
            $score >= 90 => 'healthy',
            $score >= 50 => 'warning',
            default      => 'critical',
        };
    }

    private function resolveIlan(PortfolioDriveWorkspace $workspace): ?Ilan
    {
        if (!$workspace->ilan_id) {
            return null;
        }
        return Ilan::query()->withoutGlobalScopes()->find($workspace->ilan_id);
    }
}

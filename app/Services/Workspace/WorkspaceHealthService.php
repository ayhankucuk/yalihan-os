<?php

namespace App\Services\Workspace;

use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use Illuminate\Support\Facades\DB;

/**
 * WorkspaceHealthService
 *
 * Sprint 4.6: Property Digital Twin Cockpit
 *
 * Calculates a 0–100 health score for a PortfolioDriveWorkspace across 6 dimensions:
 *   1. Documents — Drive subfolders populated
 *   2. Media     — Photos uploaded, video present
 *   3. AI        — AI workforce completion %
 *   4. CRM       — Ilan has owner / contact relationships
 *   5. Publishing — Listing publish readiness
 *   6. Compliance — Required listing fields present
 *
 * Each dimension is scored independently (0–100), then weighted to produce
 * a composite 0–100 score.
 *
 * Weighting: AI 30% · Documents 20% · Media 20% · Publishing 15% · CRM 10% · Compliance 5%
 */
class WorkspaceHealthService
{
    private const WEIGHTS = [
        'ai'         => 0.30,
        'documents'  => 0.20,
        'media'      => 0.20,
        'publishing' => 0.15,
        'crm'        => 0.10,
        'compliance' => 0.05,
    ];

    private const DRIVE_SUBFOLDERS = [
        '01_Fotograflar',
        '02_Videolar',
        '03_Tapu',
        '04_Imar',
        '05_Ekspertiz',
        '06_Airbnb',
        '07_Sahibinden',
        '08_Hepsiemlak',
        '09_CRM',
        '10_Finans',
        '11_AI',
        '12_Arsiv',
    ];

    public function __construct(
        private readonly WorkspaceNextActionService $nextActionService
    ) {}

    /**
     * Calculate full health report for a workspace.
     */
    public function calculate(PortfolioDriveWorkspace $workspace): array
    {
        $ilan = $this->resolveIlan($workspace);

        $dimensions = [
            'documents'  => $this->scoreDocuments($workspace),
            'media'      => $this->scoreMedia($ilan, $workspace),
            'ai'         => $this->scoreAi($workspace),
            'crm'        => $this->scoreCrm($ilan),
            'publishing' => $this->scorePublishing($ilan, $workspace),
            'compliance' => $this->scoreCompliance($ilan),
        ];

        $composite = $this->computeComposite($dimensions);
        $label     = $this->getLabel($composite);
        $color     = $this->getColor($composite);

        return [
            'score'              => $composite,
            'label'              => $label,
            'color'              => $color,
            'dimensions'         => $dimensions,
            'next_action'        => $this->nextActionService->recommend($workspace, $ilan, $dimensions),
            'calculated_at'      => now()->toIso8601String(),
        ];
    }

    /**
     * Documents dimension: how many Drive subfolders are populated.
     * Score = (populated / 12) * 100
     */
    public function scoreDocuments(PortfolioDriveWorkspace $workspace): array
    {
        $subfolders = $workspace->subfolders_json ?? [];
        $populated  = 0;

        foreach (self::DRIVE_SUBFOLDERS as $name) {
            foreach ($subfolders as $sf) {
                if (($sf['name'] ?? '') === $name && !empty($sf['id'])) {
                    $populated++;
                    break;
                }
            }
        }

        $score = $populated === 0
            ? 0
            : (int) round(($populated / count(self::DRIVE_SUBFOLDERS)) * 100);

        $missing = [];
        foreach (self::DRIVE_SUBFOLDERS as $name) {
            $found = false;
            foreach ($subfolders as $sf) {
                if (($sf['name'] ?? '') === $name && !empty($sf['id'])) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing[] = $name;
            }
        }

        return [
            'score'     => $score,
            'populated' => $populated,
            'total'     => count(self::DRIVE_SUBFOLDERS),
            'missing'   => $missing,
            'label'     => 'Dokümanlar',
            'weight'    => self::WEIGHTS['documents'],
        ];
    }

    /**
     * Media dimension: photos count + video presence.
     * Score = min(100, (photo_count / 5) * 50 + (has_video ? 50 : 0))
     */
    public function scoreMedia(?Ilan $ilan, PortfolioDriveWorkspace $workspace): array
    {
        $photoCount = 0;
        $hasVideo   = false;

        if ($ilan) {
            $photoCount = $ilan->fotograflar()->count();
            $hasVideo   = !empty($ilan->youtube_video_url) || !empty($ilan->sanal_tur_url);
        }

        // Drive folder check
        $drivePhotoFolderReady = false;
        $subfolders = $workspace->subfolders_json ?? [];
        foreach ($subfolders as $sf) {
            if (($sf['name'] ?? '') === '01_Fotograflar' && !empty($sf['id'])) {
                $drivePhotoFolderReady = true;
                break;
            }
        }

        $photoScore = min(50, (int) round(($photoCount / 5) * 50));
        $videoScore = $hasVideo ? 50 : ($drivePhotoFolderReady ? 10 : 0);
        $score = min(100, $photoScore + $videoScore);

        $issues = [];
        if ($photoCount < 5) {
            $issues[] = $photoCount === 0
                ? 'Hiç fotoğraf yüklenmemiş'
                : "Yalnızca {$photoCount} fotoğraf (minimum 5 önerilir)";
        }
        if (!$hasVideo) {
            $issues[] = 'Video / sanal tur yok';
        }

        return [
            'score'         => $score,
            'photo_count'   => $photoCount,
            'has_video'     => $hasVideo,
            'drive_ready'   => $drivePhotoFolderReady,
            'issues'        => $issues,
            'label'         => 'Medya',
            'weight'        => self::WEIGHTS['media'],
        ];
    }

    /**
     * AI dimension: AI workforce completion percent.
     */
    public function scoreAi(PortfolioDriveWorkspace $workspace): array
    {
        $summary    = $workspace->getAiCompletionSummary();
        $score      = (int) ($summary['percent'] ?? 0);
        $agents     = $summary['agents'] ?? [];
        $allDone    = $summary['all_complete'] ?? false;

        $issues = [];
        foreach ($agents as $agent) {
            if (!($agent['complete'] ?? false)) {
                $issues[] = $this->agentLabel($agent['name'] ?? '');
            }
        }

        return [
            'score'      => $score,
            'agents'     => $agents,
            'all_done'   => $allDone,
            'missing'    => $issues,
            'label'      => 'AI',
            'weight'     => self::WEIGHTS['ai'],
        ];
    }

    /**
     * CRM dimension: Ilan has ilan_sahibi or ilgili_kisi linked.
     */
    public function scoreCrm(?Ilan $ilan): array
    {
        $hasOwner   = false;
        $hasContact = false;

        if ($ilan) {
            $hasOwner   = $ilan->ilan_sahibi_id !== null;
            $hasContact = $ilan->ilgili_kisi_id !== null;
        }

        $score = ($hasOwner && $hasContact) ? 100 : ($hasOwner || $hasContact ? 60 : 0);

        $issues = [];
        if (!$hasOwner) {
            $issues[] = 'İlan sahibi (müşteri) bağlı değil';
        }
        if (!$hasContact) {
            $issues[] = 'İlgili kişi bağlı değil';
        }

        return [
            'score'     => $score,
            'has_owner'   => $hasOwner,
            'has_contact' => $hasContact,
            'issues'    => $issues,
            'label'     => 'CRM',
            'weight'    => self::WEIGHTS['crm'],
        ];
    }

    /**
     * Publishing dimension: listing publish readiness.
     * Based on lifecycle_state + yayin_durumu + presence of required publish fields.
     */
    public function scorePublishing(?Ilan $ilan, PortfolioDriveWorkspace $workspace): array
    {
        $state    = $workspace->lifecycle_state;
        $isLive   = $state?->isLive() ?? false;
        $isReady  = $state?->value === \App\Domain\Workspace\Enums\WorkspaceState::READY_FOR_PUBLISH->value;

        // Base score from lifecycle
        if ($isLive) {
            $lifecycleScore = 100;
        } elseif ($isReady) {
            $lifecycleScore = 85;
        } elseif ($state) {
            $lifecycleScore = max(0, $state->completionPercent());
        } else {
            $lifecycleScore = 0;
        }

        // Check required fields
        $missingFields = [];
        if ($ilan) {
            if (empty($ilan->baslik)) {
                $missingFields[] = 'Başlık';
            }
            if (empty($ilan->aciklama)) {
                $missingFields[] = 'Açıklama';
            }
            if (empty($ilan->fiyat) || $ilan->fiyat <= 0) {
                $missingFields[] = 'Fiyat';
            }
            if (!$ilan->il_id) {
                $missingFields[] = 'İl';
            }
        } else {
            $lifecycleScore = 0;
        }

        $fieldScore = count($missingFields) === 0
            ? 100
            : max(0, 100 - (count($missingFields) * 25));

        $score = (int) round(($lifecycleScore * 0.6) + ($fieldScore * 0.4));

        $issues = [];
        if ($ilan) {
            foreach ($missingFields as $field) {
                $issues[] = "{$field} eksik";
            }
        }
        if (!$isLive && !$isReady) {
            $issues[] = 'Yayın için hazır değil (lifecycle: ' . ($state?->label() ?? 'bilinmiyor') . ')';
        }

        return [
            'score'          => $score,
            'lifecycle'      => $state?->value,
            'lifecycle_label'=> $state?->label(),
            'is_live'        => $isLive,
            'is_ready'       => $isReady,
            'missing_fields' => $missingFields,
            'issues'         => $issues,
            'label'          => 'Yayın',
            'weight'         => self::WEIGHTS['publishing'],
        ];
    }

    /**
     * Compliance dimension: required listing metadata present.
     */
    public function scoreCompliance(?Ilan $ilan): array
    {
        $checks = [
            'baslik'  => $ilan && !empty($ilan->baslik),
            'fiyat'   => $ilan && $ilan->fiyat > 0,
            'alan'    => $ilan && ($ilan->alan_m2 > 0 || $ilan->net_m2 > 0),
            'oda'     => $ilan && !empty($ilan->oda),
            'ilan_sahibi' => $ilan && $ilan->ilan_sahibi_id !== null,
        ];

        $passed = count(array_filter($checks));
        $total  = count($checks);
        $score  = (int) round(($passed / $total) * 100);

        $missing = [];
        foreach ($checks as $field => $ok) {
            if (!$ok) {
                $missing[] = $field;
            }
        }

        return [
            'score'   => $score,
            'passed'  => $passed,
            'total'   => $total,
            'checks'  => $checks,
            'missing' => $missing,
            'label'   => 'Uyum',
            'weight'  => self::WEIGHTS['compliance'],
        ];
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function computeComposite(array $dimensions): int
    {
        $total = 0.0;
        foreach ($dimensions as $dim) {
            $total += ($dim['score'] / 100) * $dim['weight'];
        }
        return (int) round($total * 100);
    }

    private function getLabel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Mükemmel',
            $score >= 70 => 'İyi',
            $score >= 50 => 'Orta',
            $score >= 25 => 'Zayıf',
            default      => 'Kritik',
        };
    }

    private function getColor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'emerald',
            $score >= 70 => 'green',
            $score >= 50 => 'amber',
            $score >= 25 => 'orange',
            default      => 'red',
        };
    }

    private function resolveIlan(PortfolioDriveWorkspace $workspace): ?Ilan
    {
        if (!$workspace->ilan_id) {
            return null;
        }

        return Ilan::query()
            ->withoutGlobalScopes()
            ->find($workspace->ilan_id);
    }

    private function agentLabel(string $name): string
    {
        return match ($name) {
            'photo_agent'          => 'Fotoğraf Analizi',
            'description_agent'    => 'Açıklama Üretimi',
            'property_score_agent' => 'Mülk Skoru',
            'publish_decision_agent' => 'Yayın Kararı',
            default                => $name,
        };
    }
}

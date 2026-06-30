<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Core;

use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;

/**
 * Drift Detection Service
 *
 * Detects unauthorized changes to the database by comparing
 * live state against the governed snapshot.
 *
 * Drift Types:
 * - DRIFT: Snapshot exists, content differs
 * - SHADOW_MISSING: Snapshot exists, missing in DB
 * - UNGOVERNED: Exists in DB, not in snapshot
 */
class DriftDetectionService
{
    public function __construct(
        private ActiveConfigRegistry $registry,
        private ConfigSnapshotService $snapshotService
    ) {}

    /**
     * Detect all drifts between governed snapshot and live DB state
     */
    public function detect(): DriftReport
    {
        $activeVersion = $this->registry->getActiveVersion();

        if (!$activeVersion) {
            return new DriftReport(
                drifts: [],
                shadowMissing: [],
                ungoverned: [],
                signatureMismatch: false,
                message: 'No active configuration version found'
            );
        }

        // Get governed state
        $governedTemplates = collect($this->registry->getGovernedTemplates())
            ->keyBy(fn($t) => $t['kategori_id'] . '-' . $t['yayin_tipi_id']);

        // Capture live state
        $liveSnapshot = $this->snapshotService->capture();
        $liveTemplates = collect($liveSnapshot['templates'])
            ->keyBy(fn($t) => $t['kategori_id'] . '-' . $t['yayin_tipi_id']);

        // Detect drift types
        $drifts = [];
        $shadowMissing = [];
        $ungoverned = [];

        // Check DRIFT and SHADOW_MISSING
        foreach ($governedTemplates as $key => $govTemplate) {
            $liveTemplate = $liveTemplates->get($key);

            if (!$liveTemplate) {
                $shadowMissing[] = [
                    'key' => $key,
                    'kategori_id' => $govTemplate['kategori_id'],
                    'yayin_tipi_id' => $govTemplate['yayin_tipi_id'],
                    'name' => $govTemplate['name'] ?? 'Unknown',
                ];
                continue;
            }

            // Compare content (deterministic)
            if ($this->isDrifted($liveTemplate, $govTemplate)) {
                $drifts[] = [
                    'key' => $key,
                    'kategori_id' => $govTemplate['kategori_id'],
                    'yayin_tipi_id' => $govTemplate['yayin_tipi_id'],
                    'name' => $govTemplate['name'] ?? 'Unknown',
                    'governed_name' => $govTemplate['name'] ?? null,
                    'live_name' => $liveTemplate['name'] ?? null,
                ];
            }
        }

        // Check UNGOVERNED (exists in live, not in gov)
        foreach ($liveTemplates as $key => $liveTemplate) {
            if (!$governedTemplates->has($key)) {
                $ungoverned[] = [
                    'key' => $key,
                    'kategori_id' => $liveTemplate['kategori_id'],
                    'yayin_tipi_id' => $liveTemplate['yayin_tipi_id'],
                    'name' => $liveTemplate['name'] ?? 'Unknown',
                ];
            }
        }

        // Signature verification
        $expectedSignature = ConfigSnapshotService::computeSignature($activeVersion->snapshot_json);
        $signatureMismatch = !hash_equals($activeVersion->signature ?? '', $expectedSignature);

        return new DriftReport(
            drifts: $drifts,
            shadowMissing: $shadowMissing,
            ungoverned: $ungoverned,
            signatureMismatch: $signatureMismatch,
            message: $this->buildSummaryMessage(count($drifts), count($shadowMissing), count($ungoverned))
        );
    }

    /**
     * Check if live template differs from governed template
     */
    private function isDrifted(array $live, array $gov): bool
    {
        // Compare name
        if (($live['name'] ?? null) !== ($gov['name'] ?? null)) {
            return true;
        }

        // Compare structural data (template_json if exists)
        $liveData = $live['template_json'] ?? null;
        $govData = $gov['template_json'] ?? null;

        if ($liveData !== $govData) {
            return true;
        }

        return false;
    }

    /**
     * Build summary message
     */
    private function buildSummaryMessage(int $drifts, int $shadowMissing, int $ungoverned): string
    {
        $parts = [];

        if ($drifts > 0) {
            $parts[] = "{$drifts} content drift(s)";
        }

        if ($shadowMissing > 0) {
            $parts[] = "{$shadowMissing} shadow missing";
        }

        if ($ungoverned > 0) {
            $parts[] = "{$ungoverned} ungoverned";
        }

        if (empty($parts)) {
            return 'System is in perfect sync with governance';
        }

        return 'Drift detected: ' . implode(', ', $parts);
    }
}

/**
 * Drift Report DTO
 */
class DriftReport
{
    public function __construct(
        public array $drifts,
        public array $shadowMissing,
        public array $ungoverned,
        public bool $signatureMismatch,
        public string $message
    ) {}

    public function hasDrift(): bool
    {
        return !empty($this->drifts)
            || !empty($this->shadowMissing)
            || !empty($this->ungoverned)
            || $this->signatureMismatch;
    }

    public function getTotalCount(): int
    {
        return count($this->drifts) + count($this->shadowMissing) + count($this->ungoverned);
    }
}

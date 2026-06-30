<?php

namespace App\Modules\GovernanceCore\Contracts;

/**
 * Governance Engine Interface
 *
 * Public contract for Governance OS Core.
 * Enforces:
 * - Tenant-first API
 * - Read-only snapshot access
 * - DRAFT-only mutations
 * - Deterministic operations (same input → same output)
 *
 * @package App\Modules\GovernanceCore\Contracts
 */
interface GovernanceEngineInterface
{
    /**
     * Get active configuration version for tenant.
     *
     * @param string $tenantId Tenant identifier
     * @return array Active version metadata + snapshot
     * @throws \App\Exceptions\CriticalGovernanceException If no active version or signature mismatch
     */
    public function getActiveVersion(string $tenantId): array;

    /**
     * Calculate risk score for a version.
     *
     * Deterministic: same snapshot → same risk score.
     *
     * @param string $tenantId Tenant identifier
     * @param string $versionHash Version hash to analyze
     * @return array Risk score + level + breakdown
     */
    public function calculateRisk(string $tenantId, string $versionHash): array;

    /**
     * Detect drift between active snapshot and database state.
     *
     * Side-effect free: NO database writes, NO ACTIVE mutations.
     *
     * @param string $tenantId Tenant identifier
     * @return array Drift summary (value_drifts, shadow_missing, ungoverned_records)
     */
    public function detectDrift(string $tenantId): array;

    /**
     * Simulate activation of a DRAFT version (dry-run).
     *
     * Returns impact analysis without activating.
     * ACTIVE version remains unchanged.
     *
     * @param string $tenantId Tenant identifier
     * @param string $versionHash DRAFT version hash to simulate
     * @return array Impact summary (affected_features, risk_delta, estimated_drift)
     */
    public function simulateActivation(string $tenantId, string $versionHash): array;

    /**
     * Export governance timeline as JSON with hash chain.
     *
     * Deterministic: same tenant + same time range → same export hash.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeInterface|null $since Export versions since this date (null = all)
     * @return string File path to exported JSON
     */
    public function exportTimeline(string $tenantId, ?\DateTimeInterface $since = null): string;

    /**
     * Verify integrity of exported governance timeline.
     *
     * Validates:
     * - Hash chain continuity
     * - Signature authenticity
     * - Snapshot SHA256 integrity
     *
     * @param string $filePath Path to exported JSON file
     * @return bool True if export is valid and untempered
     * @throws \InvalidArgumentException If file format invalid
     */
    public function verifyExport(string $filePath): bool;

    /**
     * Get tenant risk analytics (heatmap + maturity score).
     *
     * Cached: p95 < 200ms.
     * Deterministic: same tenant state → same metrics.
     *
     * @param string $tenantId Tenant identifier
     * @return array Risk heatmap + maturity score + entropy metrics
     */
    public function getTenantRiskAnalytics(string $tenantId): array;

    /**
     * Get governance maturity score for tenant.
     *
     * Score components:
     * - Average risk score (recent 30 versions)
     * - Critical incident count
     * - Configuration stability
     *
     * @param string $tenantId Tenant identifier
     * @return array Maturity score (0-100) + maturity band + entropy index
     */
    public function getMaturityScore(string $tenantId): array;

    /**
     * Get drift history for tenant.
     *
     * Returns timeline of all detected drift incidents.
     *
     * @param string $tenantId Tenant identifier
     * @param int $limit Maximum number of incidents to return
     * @return array Drift incidents ordered by detection time (newest first)
     */
    public function getDriftHistory(string $tenantId, int $limit = 50): array;

    /**
     * Predictive impact analysis for proposed DRAFT version.
     *
     * Uses historical patterns to forecast activation risk.
     *
     * @param string $tenantId Tenant identifier
     * @param string $draftVersionHash DRAFT version hash
     * @return array Predicted risk + confidence interval + recommendations
     */
    public function predictImpact(string $tenantId, string $draftVersionHash): array;
}

<?php

declare(strict_types=1);

namespace App\Services\Listing;

use App\Models\Ilan;
use App\Domain\Listing\ListingCrudService as NewListingCrudService;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Support\Facades\Log;

/**
 * ListingCrudBridge
 *
 * Sprint 12C: Dual-execution bridge for IlanCrudService → ListingCrudService migration.
 *
 * Flow:
 * ```
 * Controller → Bridge → [Shadow Mode: Both] → Compare results
 *                  └→ [Normal Mode: Single] → Return result
 * ```
 *
 * Feature Flags:
 * - listing_crud_v2_enabled: Use ListingCrudService
 * - listing_crud_v2_shadow: Run both, return legacy result, log differences
 */
class ListingCrudBridge
{
    protected IlanCrudService $legacyService;
    protected NewListingCrudService $newService;

    public function __construct(
        ?IlanCrudService $legacyService = null,
        ?NewListingCrudService $newService = null,
    ) {
        $this->legacyService = $legacyService ?? app(IlanCrudService::class);
        $this->newService = $newService ?? app(NewListingCrudService::class);
    }

    /**
     * Store a new listing.
     *
     * @param array $data Listing data
     * @param int|null $workspaceId Workspace ID
     * @return Ilan
     */
    public function store(array $data, ?int $workspaceId = null): Ilan
    {
        if ($this->shouldUseLegacy()) {
            return $this->legacyService->store($data);
        }

        return $this->executeWithShadow(
            'store',
            fn() => $this->newService->createFromProperty(
                $data['property'] ?? null,
                $data,
                $workspaceId
            ),
            fn() => $this->legacyService->store($data)
        );
    }

    /**
     * Update an existing listing.
     *
     * @param Ilan $ilan
     * @param array $data
     * @return Ilan
     */
    public function update(Ilan $ilan, array $data): Ilan
    {
        if ($this->shouldUseLegacy()) {
            return $this->legacyService->update($ilan, $data);
        }

        return $this->executeWithShadow(
            'update',
            fn() => $this->newService->update($ilan, $data),
            fn() => $this->legacyService->update($ilan, $data)
        );
    }

    /**
     * Submit listing for review.
     *
     * @param Ilan $ilan
     * @param int|null $aktanId
     * @return Ilan
     */
    public function submitForReview(Ilan $ilan, ?int $aktanId = null): Ilan
    {
        if ($this->shouldUseLegacy()) {
            return $this->legacyService->submitForReview($ilan, $aktanId);
        }

        return $this->executeWithShadow(
            'submitForReview',
            fn() => $this->newService->submitForReview($ilan, $aktanId),
            fn() => $this->legacyService->submitForReview($ilan, $aktanId)
        );
    }

    /**
     * Publish listing.
     *
     * @param Ilan $ilan
     * @param int|null $aktanId
     * @return Ilan
     */
    public function publish(Ilan $ilan, ?int $aktanId = null): Ilan
    {
        if ($this->shouldUseLegacy()) {
            return $this->legacyService->publish($ilan, $aktanId);
        }

        return $this->executeWithShadow(
            'publish',
            fn() => $this->newService->publish($ilan, $aktanId),
            fn() => $this->legacyService->publish($ilan, $aktanId)
        );
    }

    /**
     * Unpublish listing.
     *
     * @param Ilan $ilan
     * @param string $reason
     * @param int|null $aktanId
     * @return Ilan
     */
    public function unpublish(Ilan $ilan, string $reason = '', ?int $aktanId = null): Ilan
    {
        if ($this->shouldUseLegacy()) {
            return $this->legacyService->unpublish($ilan, $reason, $aktanId);
        }

        return $this->executeWithShadow(
            'unpublish',
            fn() => $this->newService->unpublish($ilan, $reason, $aktanId),
            fn() => $this->legacyService->unpublish($ilan, $reason, $aktanId)
        );
    }

    /**
     * Archive listing.
     *
     * @param Ilan $ilan
     * @param string $reason
     * @param int|null $aktanId
     * @return Ilan
     */
    public function archive(Ilan $ilan, string $reason = '', ?int $aktanId = null): Ilan
    {
        if ($this->shouldUseLegacy()) {
            return $this->legacyService->archive($ilan, $reason, $aktanId);
        }

        return $this->executeWithShadow(
            'archive',
            fn() => $this->newService->archive($ilan, $reason, $aktanId),
            fn() => $this->legacyService->archive($ilan, $reason, $aktanId)
        );
    }

    /**
     * Destroy listing (soft delete via archive).
     *
     * CRITICAL: Shadow mode'da destroy işlemi YALNIZCA legacy çalıştırır.
     * Bunun nedeni: Her iki servis de gerçek silme yapmasın diye.
     *
     * Shadow modu aktifse:
     * - V2 servisi izole transaction'da çalışır (rollback)
     * - Legacy gerçek sonucu döner
     * - Sadece karşılaştırma loglanır
     *
     * @param Ilan $ilan
     * @param int|null $aktanId
     * @return Ilan
     */
    public function destroy(Ilan $ilan, ?int $aktanId = null): Ilan
    {
        if ($this->shouldUseLegacy()) {
            return $this->legacyService->destroy($ilan, $aktanId);
        }

        // Shadow modunda destroy: sadece legacy çalışır, V2 rollback
        if ($this->isShadowMode()) {
            return $this->executeDestroyShadow($ilan, $aktanId);
        }

        return $this->newService->delete($ilan, '', $aktanId);
    }

    /**
     * Destroy in shadow mode - legacy runs, V2 rolls back.
     */
    protected function executeDestroyShadow(Ilan $ilan, ?int $aktanId): Ilan
    {
        $v2Success = false;
        $v2Error = null;

        // V2 runs in isolated transaction (will rollback)
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($ilan, $aktanId, &$v2Success) {
                $this->newService->delete($ilan, '', $aktanId);
                $v2Success = true;
                throw new \Exception('ROLLBACK_ONLY'); // Force rollback
            });
        } catch (\Throwable $e) {
            if ($e->getMessage() === 'ROLLBACK_ONLY') {
                // Expected - V2 ran but rolled back
            } else {
                $v2Error = $e;
            }
        }

        // Legacy runs for real
        $legacyResult = $this->legacyService->destroy($ilan, $aktanId);

        // Log comparison
        $this->logShadowComparison('destroy', null, $legacyResult, $v2Error, null, [
            'v2_ran' => true,
            'v2_rolled_back' => true,
            'legacy_executed' => true,
        ]);

        return $legacyResult;
    }

    /**
     * Check if shadow mode is active.
     */
    protected function isShadowMode(): bool
    {
        return config('feature-flags.listing_crud_v2_shadow', false) === true
            && config('feature-flags.listing_crud_v2_enabled', false) === true;
    }

    /**
     * Check if legacy service should be used.
     */
    protected function shouldUseLegacy(): bool
    {
        // Feature flag disabled = use legacy
        if (!config('feature-flags.listing_crud_v2_enabled', false)) {
            return true;
        }

        // Check allowlist
        $allowlist = config('feature-flags.listing_crud_v2_allowlist', []);

        // No allowlist = all allowed with new service
        if (empty($allowlist['tenant_ids']) && empty($allowlist['routes'])) {
            return false;
        }

        // Check tenant allowlist
        if (!empty($allowlist['tenant_ids'])) {
            $currentTenantId = $this->getCurrentTenantId();
            if ($currentTenantId && in_array($currentTenantId, $allowlist['tenant_ids'])) {
                return false; // Use new service
            }
        }

        // Check route allowlist
        if (!empty($allowlist['routes'])) {
            $currentRoute = $this->getCurrentRoute();
            if ($currentRoute && in_array($currentRoute, $allowlist['routes'])) {
                return false; // Use new service
            }
        }

        // Not in allowlist = use legacy
        return true;
    }

    /**
     * Get current tenant ID from context.
     */
    protected function getCurrentTenantId(): ?int
    {
        try {
            $tenant = app(\App\Services\SaaS\TenantContextService::class)->getTenant();
            return $tenant?->id;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get current route name.
     */
    protected function getCurrentRoute(): ?string
    {
        try {
            return \Illuminate\Support\Facades\Route::currentRouteName();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Execute with shadow mode comparison.
     *
     * @param string $method Method name for logging
     * @param callable $newFn New service function
     * @param callable $legacyFn Legacy service function
     * @return Ilan
     */
    protected function executeWithShadow(
        string $method,
        callable $newFn,
        callable $legacyFn,
    ): Ilan {
        $shadow = config('feature-flags.listing_crud_v2_shadow', false);

        if ($shadow) {
            return $this->executeShadow($method, $newFn, $legacyFn);
        }

        return $newFn();
    }

    /**
     * Execute shadow mode: run both, compare, return legacy.
     */
    protected function executeShadow(
        string $method,
        callable $newFn,
        callable $legacyFn,
    ): Ilan {
        $newResult = null;
        $legacyResult = null;
        $newError = null;
        $legacyError = null;

        // Execute new service
        try {
            $newResult = $newFn();
        } catch (\Throwable $e) {
            $newError = $e;
            Log::warning("ListingCrudBridge [{$method}] new service error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Execute legacy service
        try {
            $legacyResult = $legacyFn();
        } catch (\Throwable $e) {
            $legacyError = $e;
            Log::warning("ListingCrudBridge [{$method}] legacy service error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Log comparison
        $this->logShadowComparison($method, $newResult, $legacyResult, $newError, $legacyError);

        // Return legacy result (production behavior)
        if ($legacyError) {
            throw $legacyError;
        }

        return $legacyResult;
    }

    /**
     * Log shadow comparison results.
     *
     * @param string $method Operation name
     * @param Ilan|null $newResult V2 service result
     * @param Ilan|null $legacyResult Legacy service result
     * @param Throwable|null $newError V2 error
     * @param Throwable|null $legacyError Legacy error
     * @param array $extra Additional metadata (e.g., destroy-specific)
     */
    protected function logShadowComparison(
        string $method,
        ?Ilan $newResult,
        ?Ilan $legacyResult,
        ?\Throwable $newError,
        ?\Throwable $legacyError,
        array $extra = [],
    ): void {
        $comparison = [
            'method' => $method,
            'call_site' => $this->getCurrentRoute() ?? 'unknown',
            'tenant_id' => $this->getCurrentTenantId(),
            'workspace_id' => $this->getCurrentWorkspaceId(),
            'new_success' => $newResult !== null && $newError === null,
            'legacy_success' => $legacyResult !== null && $legacyError === null,
            'both_success' => $newResult !== null && $legacyResult !== null,
            'results_match' => false,
            'duration_ms' => null,
            'correlation_id' => $this->generateCorrelationId(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Add extra metadata
        $comparison = array_merge($comparison, $extra);

        // Compare results for parity
        if ($newResult && $legacyResult) {
            $comparison['results_match'] = $this->compareResults($newResult, $legacyResult);
            $comparison['difference_fields'] = $this->getDifferenceFields($newResult, $legacyResult);
        }

        // Log to shadow channel
        Log::channel('shadow')->info('ListingCrudBridge shadow comparison', $comparison);
    }

    /**
     * Get current workspace ID.
     */
    protected function getCurrentWorkspaceId(): ?int
    {
        try {
            $workspace = app(\App\Services\SaaS\TenantContextService::class)->getWorkspace();
            return $workspace?->id;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Generate correlation ID for request tracing.
     */
    protected function generateCorrelationId(): string
    {
        return sprintf(
            '%s-%s',
            now()->format('Ymd-His'),
            bin2hex(random_bytes(4))
        );
    }

    /**
     * Get fields that differ between results.
     */
    protected function getDifferenceFields(Ilan $new, Ilan $legacy): array
    {
        $fields = ['yayin_durumu', 'tenant_id', 'workspace_id', 'baslik', 'fiyat', 'aktiflik_durumu'];
        $differences = [];

        foreach ($fields as $field) {
            if ($new->$field !== $legacy->$field) {
                $differences[] = [
                    'field' => $field,
                    'legacy' => $legacy->$field,
                    'new' => $new->$field,
                ];
            }
        }

        return $differences;
    }

    /**
     * Compare results for parity validation.
     */
    protected function compareResults(Ilan $new, Ilan $legacy): bool
    {
        $fields = ['yayin_durumu', 'tenant_id', 'workspace_id', 'baslik', 'fiyat'];

        foreach ($fields as $field) {
            if ($new->$field !== $legacy->$field) {
                return false;
            }
        }

        return true;
    }
}

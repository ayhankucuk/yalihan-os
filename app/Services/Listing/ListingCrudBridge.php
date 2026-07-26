<?php

declare(strict_types=1);

namespace App\Services\Listing;

use App\Models\Ilan;
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
    protected ListingCrudService $newService;

    public function __construct(
        ?IlanCrudService $legacyService = null,
        ?ListingCrudService $newService = null,
    ) {
        $this->legacyService = $legacyService ?? app(IlanCrudService::class);
        $this->newService = $newService ?? app(ListingCrudService::class);
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
     * @param Ilan $ilan
     * @param int|null $aktanId
     * @return Ilan
     */
    public function destroy(Ilan $ilan, ?int $aktanId = null): Ilan
    {
        if ($this->shouldUseLegacy()) {
            return $this->legacyService->destroy($ilan, $aktanId);
        }

        return $this->executeWithShadow(
            'destroy',
            fn() => $this->newService->delete($ilan, '', $aktanId),
            fn() => $this->legacyService->destroy($ilan, $aktanId)
        );
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

        return false;
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
     */
    protected function logShadowComparison(
        string $method,
        ?Ilan $newResult,
        ?Ilan $legacyResult,
        ?\Throwable $newError,
        ?\Throwable $legacyError,
    ): void {
        $comparison = [
            'method' => $method,
            'new_success' => $newResult !== null && $newError === null,
            'legacy_success' => $legacyResult !== null && $legacyError === null,
            'both_success' => $newResult !== null && $legacyResult !== null,
            'results_match' => false,
        ];

        if ($newResult && $legacyResult) {
            $comparison['results_match'] = $this->compareResults($newResult, $legacyResult);
        }

        Log::channel('shadow')->info('ListingCrudBridge shadow comparison', $comparison);
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

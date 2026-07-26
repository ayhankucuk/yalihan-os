<?php

declare(strict_types=1);

namespace App\Application\Listing\Services;

use App\Application\Listing\Commands\SubmitIlanWizardCommand;
use App\Application\Listing\Results\IlanWizardSubmissionResult;
use App\Exceptions\TemplateCategoryMismatchException;
use App\Exceptions\TemplateNotFoundException;
use App\Models\Ilan;
use App\Services\Listing\ListingCrudBridge;
use App\Services\Wizard\EffectiveListingTypeResolver;
use App\Services\Wizard\WizardGateService;
use Illuminate\Support\Facades\Log;

/**
 * IlanWizardApplicationService
 *
 * Sprint 12C Wave 2: IlanWizardController migration
 *
 * Application-layer orchestration service for listing wizard submission.
 * HTTP-independent — does not accept Request, Session or RedirectResponse.
 *
 * Responsibilities:
 * - Wizard submission orchestration
 * - Business policy invocation
 * - Template gate coordination
 * - Idempotency coordination
 * - ListingCrudBridge invocation
 * - Result DTO production
 *
 * Write Authority:
 * - All persistence goes through ListingCrudBridge
 * - No direct Eloquent writes
 * - Single canonical write path
 *
 * Feature Flag Behavior:
 * - OFF: Legacy IlanCrudService
 * - ON: New ListingCrudService
 * - SHADOW: Both, return legacy, log comparison
 */
class IlanWizardApplicationService
{
    public function __construct(
        private readonly ListingCrudBridge $bridge,
        private readonly EffectiveListingTypeResolver $listingTypeResolver,
        private readonly WizardGateService $gateService,
    ) {}

    /**
     * Submit wizard listing
     *
     * Orchestrates the complete wizard submission workflow.
     * Routes listing creation through ListingCrudBridge.
     *
     * @param SubmitIlanWizardCommand $command
     * @return IlanWizardSubmissionResult
     */
    public function submit(SubmitIlanWizardCommand $command): IlanWizardSubmissionResult
    {
        // 1. Validate required wizard steps
        if (empty($command->step1) || empty($command->step3)) {
            return IlanWizardSubmissionResult::incompleteWizard();
        }

        // 2. Category + Publication Type Policy Validation
        $policyResult = $this->validatePublicationPolicy($command);
        if ($policyResult !== null) {
            return $policyResult;
        }

        // 3. Template Gate Validation
        $templateResult = $this->validateTemplateGate($command);
        if ($templateResult !== null) {
            return $templateResult;
        }

        // 4. Build canonical payload with all wizard data
        $payload = $command->toPayload();

        try {
            // 5. Route through ListingCrudBridge
            // - OFF mode: IlanCrudService (legacy)
            // - ON mode: ListingCrudService (V2)
            // - SHADOW mode: Both, return legacy, log comparison
            $ilan = $this->bridge->store($payload, $command->workspaceId);

            Log::info('IlanWizardApplicationService: Wizard submission successful', [
                'ilan_id' => $ilan->id,
                'actor_id' => $command->actorId,
                'workspace_id' => $command->workspaceId,
                'bridge_mode' => $this->getBridgeMode(),
            ]);

            return IlanWizardSubmissionResult::success($ilan->id, [
                'ilan' => $ilan->toArray(),
            ]);

        } catch (\Throwable $e) {
            Log::error('IlanWizardApplicationService: Wizard submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'actor_id' => $command->actorId,
            ]);

            return IlanWizardSubmissionResult::serverError($e->getMessage(), $e);
        }
    }

    /**
     * Validate category + publication type policy
     *
     * @param SubmitIlanWizardCommand $command
     * @return IlanWizardSubmissionResult|null Returns error result if validation fails
     */
    protected function validatePublicationPolicy(SubmitIlanWizardCommand $command): ?IlanWizardSubmissionResult
    {
        $yayinTipiId = $command->getYayinTipiId();
        $categoryIds = $command->getCategoryIds();

        if (!$yayinTipiId || !$categoryIds['main']) {
            return null;
        }

        if (!$this->listingTypeResolver->isAllowed(
            $categoryIds['main'],
            $categoryIds['sub'],
            $yayinTipiId
        )) {
            return IlanWizardSubmissionResult::publicationTypeNotAllowed();
        }

        return null;
    }

    /**
     * Validate template gate
     *
     * @param SubmitIlanWizardCommand $command
     * @return IlanWizardSubmissionResult|null Returns error result if validation fails
     */
    protected function validateTemplateGate(SubmitIlanWizardCommand $command): ?IlanWizardSubmissionResult
    {
        $yayinTipiId = $command->getYayinTipiId();

        if (!$yayinTipiId) {
            return null;
        }

        $kategoriId = (int) ($command->step1['kategori_id'] ?? 0);

        try {
            $this->gateService->dogrulaWizardGirisi($yayinTipiId, $kategoriId ?: null);
        } catch (TemplateNotFoundException $e) {
            return IlanWizardSubmissionResult::templateNotFound();
        } catch (TemplateCategoryMismatchException $e) {
            return IlanWizardSubmissionResult::templateCategoryMismatch();
        }

        return null;
    }

    /**
     * Get current bridge mode for logging
     */
    protected function getBridgeMode(): string
    {
        $shadow = config('feature-flags.listing_crud_v2_shadow', false);
        $enabled = config('feature-flags.listing_crud_v2_enabled', false);

        if ($shadow && $enabled) {
            return 'SHADOW';
        }

        if ($enabled) {
            return 'ON';
        }

        return 'OFF';
    }
}

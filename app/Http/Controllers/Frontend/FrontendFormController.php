<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ContactFormRequest;
use App\Models\Ilan;
use App\Models\SaaS\Tenant;
use App\Services\Frontend\FrontendFormService;
use Illuminate\Http\RedirectResponse;

/**
 * FrontendFormController
 *
 * Handles public-facing form submissions from property detail pages.
 *
 * Flow:
 *   1. Validate ACTUAL Blade field names (name, phone, message, ilan_id)
 *   2. Resolve tenant from Ilan (unauthenticated — no auth()->user()->tenant_id)
 *   3. Delegate CRM writes to FrontendFormService (canonical path)
 *   4. Redirect back with success flash
 *
 * Corrections applied:
 *   - CORRECTION 1: Field names now match Blade (name/phone/message/ilan_id)
 *   - CORRECTION 2: Uses FrontendFormService which delegates to TalepAuthorityService
 *   - CORRECTION 3: No Eslesme creation (matching belongs to domain pipeline)
 *
 * Task: WEB_PROPERTY_DETAIL_CRM_CONTACT_REMEDIATION_16
 */
class FrontendFormController extends Controller
{
    public function __construct(
        private readonly FrontendFormService $formService
    ) {}

    public function submit(ContactFormRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Tenant resolution: Ilan is the source of truth for tenant_id
        // (every listing belongs to exactly one tenant)
        $ilan = Ilan::withoutGlobalScopes()
            ->select(['id', 'tenant_id', 'baslik', 'danisman_id', 'il_id', 'ilce_id', 'mahalle_id'])
            ->findOrFail((int) $validated['ilan_id']);

        $tenant = Tenant::findOrFail($ilan->tenant_id);

        // Delegate all CRM writes to canonical service path
        $this->formService->processContactForm($validated, $ilan, $tenant);

        return redirect()
            ->back()
            ->with('success', 'Mesajınız başarıyla iletildi. En kısa sürede sizinle iletişime geçeceğiz.');
    }
}

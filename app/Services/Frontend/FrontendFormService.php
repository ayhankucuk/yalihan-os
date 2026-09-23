<?php

declare(strict_types=1);

namespace App\Services\Frontend;

use App\Enums\KisiTipi;
use App\Enums\TalepDurumu;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\SaaS\Tenant;
use App\Models\Talep;
use App\Services\CRM\KisiRegistrationService;
use App\Services\CRM\TalepAuthorityService;
use App\Services\Logging\LogService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\Log;

/**
 * FrontendFormService
 *
 * Handles contact form CRM writes for unauthenticated frontend users.
 *
 * Canonical path enforcement (Task WEB_PROPERTY_DETAIL_CRM_CONTACT_REMEDIATION_16):
 *   1. findOrCreateKisi()       → KisiRegistrationService::register() (authority)
 *   2. createTalep()            → TalepAuthorityService::createTalep()  (authority)
 *   3. NO Eslesme creation     → matching belongs to domain pipeline
 *
 * Corrections applied:
 *   - CORRECTION 2: Uses TalepAuthorityService (canonical write authority)
 *   - CORRECTION 3: Removes Eslesme creation (matching is a domain pipeline concern)
 *   - CORRECTION 4: Removes auth()->id() calls (unauthenticated frontend context)
 *   - Uses enum TalepDurumu::AKTIF instead of string literal
 *
 * Task: WEB_PROPERTY_DETAIL_CRM_CONTACT_REMEDIATION_16
 */
class FrontendFormService
{
    public function __construct(
        private readonly TenantContextService $tenantContextService,
        private readonly KisiRegistrationService $kisiRegistrationService,
        private readonly TalepAuthorityService $talepAuthorityService
    ) {}

    /**
     * Process a property contact form submission.
     *
     * @param array $validated Normalized CRM field names: ad, soyad, telefon, mesaj, ilan_id
     * @return array{kisi: Kisi, talep: Talep}
     */
    public function processContactForm(array $validated, Ilan $ilan, Tenant $tenant): array
    {
        // Set tenant context so BelongsToTenant auto-populates tenant_id on all models
        $this->tenantContextService->setTenant($tenant);

        $kisi = $this->findOrCreateKisi($validated, $ilan, $tenant);

        // Canonical Talep creation via authority service (CORRECTION 2)
        $talep = $this->createTalep($validated, $ilan, $kisi, $tenant);

        Log::channel('governance')->info('Frontend contact form: Kisi + Talep created', [
            'kisi_id'  => $kisi->id,
            'talep_id' => $talep->id,
            'ilan_id'  => $ilan->id,
            'tenant_id' => $tenant->id,
            'source'   => 'frontend.contact_form',
        ]);

        return ['kisi' => $kisi, 'talep' => $talep];
    }

    /**
     * Find existing Kisi by telefon or register new via canonical authority.
     *
     * Uses KisiRegistrationService for proper duplicate detection and
     * consistent CRM audit trail.
     */
    private function findOrCreateKisi(array $validated, Ilan $ilan, Tenant $tenant): Kisi
    {
        $telefon = $this->normalizeTelefon($validated['telefon'] ?? '');

        // Check for existing Kisi within tenant scope
        $existing = Kisi::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('telefon', $telefon)
            ->first();

        if ($existing) {
            // Refresh contact info if changed
            return $this->refreshKisi($existing, $validated);
        }

        // Register via canonical authority service (null userId = no authenticated actor)
        $kisiData = [
            'ad'        => $validated['ad'] ?? '',
            'soyad'      => $validated['soyad'] ?? '',
            'telefon'    => $telefon,
            'eposta'     => $validated['eposta'] ?? null,
            'kisi_tipi'  => KisiTipi::ALICI->value,
            'kaynak'     => 'frontend_ilan_form',
            'notlar'     => $this->buildKisiNote($validated, $ilan),
            'danisman_id' => $ilan->danisman_id,
        ];

        return $this->kisiRegistrationService->register($kisiData, null);
    }

    /**
     * Refresh existing Kisi contact fields if new values provided.
     */
    private function refreshKisi(Kisi $kisi, array $validated): Kisi
    {
        $updateFields = array_filter([
            'ad'     => $validated['ad'] ?? null,
            'soyad'   => $validated['soyad'] ?? null,
            'eposta'  => $validated['eposta'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($updateFields)) {
            $this->kisiRegistrationService->update($kisi, $updateFields);
        }

        return $kisi->fresh();
    }

    /**
     * Create Talep via canonical TalepAuthorityService.
     *
     * Uses TalepAuthorityService spillover format so the service handles
     * kisi_id creation if only name/contact fields are provided.
     */
    private function createTalep(array $validated, Ilan $ilan, Kisi $kisi, Tenant $tenant): Talep
    {
        // TalepAuthorityService spillover format — service handles tenant_id via
        // BelongsToTenant trait (tenant context already set on TenantContextService)
        $talepData = [
            'baslik'       => 'Web Form: ' . $ilan->baslik,
            'talep_tipi'   => 'alis',
            'talep_durumu' => TalepDurumu::AKTIF->value,
            'kisi_id'      => $kisi->id,
            'danisman_id'  => $ilan->danisman_id,
            'ilan_id'      => $ilan->id,
            'il_id'        => $ilan->il_id,
            'ilce_id'      => $ilan->ilce_id,
            'notlar'       => $this->buildTalepNote($validated, $ilan),
            'kaynak'       => 'frontend_ilan_form',
            // Spillover fields (used if kisi_id was missing — not the case here,
            // but sent for consistency with TalepAuthorityService contract)
            'kisi_ad'      => $validated['ad'] ?? null,
            'kisi_soyad'   => $validated['soyad'] ?? null,
            'kisi_telefon' => $this->normalizeTelefon($validated['telefon'] ?? ''),
            'kisi_email'   => $validated['eposta'] ?? null,
        ];

        return $this->talepAuthorityService->createTalep($talepData, null);
    }

    private function normalizeTelefon(string $telefon): string
    {
        return preg_replace('/[\s\-\(\)]+/', '', trim($telefon));
    }

    private function buildKisiNote(array $validated, Ilan $ilan): ?string
    {
        $note = 'Frontend ilan formu üzerinden oluşturuldu.';
        if (!empty($validated['mesaj'])) {
            $note .= "\n\n--- Orijinal Mesaj ---\n" . $validated['mesaj'];
        }
        $note .= "\n\nİlan: {$ilan->baslik} (ID: {$ilan->id})";
        return $note;
    }

    private function buildTalepNote(array $validated, Ilan $ilan): ?string
    {
        $note = 'Frontend ilan formu üzerinden oluşturuldu.';
        if (!empty($validated['mesaj'])) {
            $note .= "\n\n--- Mesaj ---\n" . $validated['mesaj'];
        }
        return $note;
    }
}

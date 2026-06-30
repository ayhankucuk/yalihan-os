<?php

declare(strict_types=1);

namespace Tests\Feature\Wizard;

use App\Services\Wizard\WizardContextService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

/**
 * WizardContextService — Fallback Removal Tests
 *
 * Governance Enforcement Layer kaldırıldığında:
 * silent fallback (success: true + hardcoded template) dönülemez.
 * Çözümleme başarısız olursa exception fırlatılmalı.
 *
 * @see app/Services/Wizard/WizardContextService.php
 * @see docs/adr/2026-02-21-governance-enforcement-layer.md
 */
class WizardContextFallbackRemovalTest extends TestCase
{

    /**
     * Geçersiz kategori_id ile resolve() çağrılınca exception fırlatılmalı —
     * success: true + fallback template DÖNMEMELİ.
     *
     * @test
     */
    public function resolve_throws_on_invalid_kategori_not_returns_fallback(): void
    {
        /** @var WizardContextService $service */
        $service = app(WizardContextService::class);

        $this->expectException(\Throwable::class);

        // ID 999999 — DB'de yok → ModelNotFoundException → outer catch → throw $e
        $result = $service->resolve(999999, 999999);

        // Buraya asla ulaşılmamalı — ama ulaşılırsa fallback dönüldüğünü gösterir
        $this->assertNotEquals(true, $result['success'] ?? false,
            'WizardContextService should NOT return success:true as a silent fallback — it must throw.'
        );
    }

    /**
     * Hata durumunda dönen başarı yanıtı kesinlikle yasak
     *
     * @test
     */
    public function resolve_does_not_swallow_exceptions_silently(): void
    {
        /** @var WizardContextService $service */
        $service = app(WizardContextService::class);

        $exceptionThrown = false;

        try {
            $service->resolve(0, 0); // Her zaman fail
        } catch (\Throwable $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue(
            $exceptionThrown,
            'WizardContextService::resolve() must propagate exceptions. Silent fallback (success:true) is forbidden per Governance Enforcement Layer ADR.'
        );
    }
}

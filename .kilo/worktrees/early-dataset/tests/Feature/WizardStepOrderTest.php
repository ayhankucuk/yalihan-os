<?php

namespace Tests\Feature;

use App\Models\IlanKategori;
use App\Models\YayinTipi;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use Tests\TestCase;

/**
 * Wizard Step Order Test
 *
 * Context7: Ensures wizard step sequence remains correct after edits
 */
class WizardStepOrderTest extends TestCase
{
    public function test_wizard_blade_has_correct_step_labels()
    {
        $bladePath = resource_path('views/admin/ilanlar/create-wizard.blade.php');
        $this->assertFileExists($bladePath);

        $content = file_get_contents($bladePath);

        // Check step labels exist
        $this->assertStringContainsString('1. Kategori', $content);
        $this->assertStringContainsString('2. Bilgiler', $content);
        $this->assertStringContainsString('3. Fotoğraf', $content);
        $this->assertStringContainsString('4. Adres', $content);
        $this->assertStringContainsString('5. Önizleme', $content);
    }

    public function test_wizard_step_order_is_correct()
    {
        $bladePath = resource_path('views/admin/ilanlar/create-wizard.blade.php');
        $content = file_get_contents($bladePath);

        // Find positions of step labels
        $bilgilerPos = strpos($content, '2. Bilgiler');
        $fotografPos = strpos($content, '3. Fotoğraf');

        // Bilgiler should come before Fotoğraf
        $this->assertNotFalse($bilgilerPos, 'Bilgiler label not found');
        $this->assertNotFalse($fotografPos, 'Fotoğraf label not found');
        $this->assertLessThan($fotografPos, $bilgilerPos, 'Bilgiler should come before Fotoğraf');
    }

    public function test_wizard_step_content_blocks_are_correctly_ordered()
    {
        $bladePath = resource_path('views/admin/ilanlar/create-wizard.blade.php');
        $content = file_get_contents($bladePath);

        // Use x-show to find actual step content divs (not navigation buttons)
        $step2Start = strpos($content, 'x-show="wizard?.currentStep === 2"');
        $step3Start = strpos($content, 'x-show="wizard?.currentStep === 3"');

        $this->assertNotFalse($step2Start, 'Step 2 x-show directive not found');
        $this->assertNotFalse($step3Start, 'Step 3 x-show directive not found');

        // Extract content between step 2 and step 3 markers
        $step2Content = substr($content, $step2Start, $step3Start - $step2Start);

        // Step 2 should include step-2-info (Bilgiler) - renamed from step-3-info-without-features
        $this->assertStringContainsString('step-2-info', $step2Content, 'Step 2 should contain Bilgiler form');

        // Extract step 3 content
        $step4Start = strpos($content, 'x-show="wizard?.currentStep === 4"');
        $step3Content = substr($content, $step3Start, $step4Start - $step3Start);

        // Step 3 should include step-3-photos (Fotoğraf) - renamed from step-2-photos
        $this->assertStringContainsString('step-3-photos', $step3Content, 'Step 3 should contain Fotoğraf upload');
    }
}

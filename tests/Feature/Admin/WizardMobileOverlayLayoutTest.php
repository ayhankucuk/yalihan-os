<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Wizard Mobile Overlay & Admin Layout Regression Test
 *
 * Verifies fixes for TASK_ID: WIZARD_MOBILE_OVERLAY_FIX_01:
 * 1. Admin layout contains effective [x-cloak] { display: none !important; } CSS rule in <head>.
 * 2. Copilot widget uses responsive width w-[calc(100vw-3rem)] max-w-sm sm:w-96 (no fixed w-96 overflow on 375px mobile).
 * 3. Copilot diff modal container contains x-cloak attribute to prevent pre-init Alpine rendering/flashing.
 * 4. Desktop sm:w-96 styling is preserved for viewports >= 640px.
 */
class WizardMobileOverlayLayoutTest extends TestCase
{
    /**
     * @test
     * 1. Admin layout head defines the global [x-cloak] CSS rule.
     */
    public function it_verifies_admin_layout_defines_x_cloak_css_rule(): void
    {
        $bladePath = resource_path('views/admin/layouts/admin.blade.php');
        $this->assertFileExists($bladePath);

        $content = file_get_contents($bladePath);

        $this->assertStringContainsString('[x-cloak]', $content);
        $this->assertStringContainsString('display: none !important;', $content);
    }

    /**
     * @test
     * 2. Copilot widget uses responsive width classes and avoids fixed w-96 alone.
     */
    public function it_verifies_copilot_widget_uses_responsive_width(): void
    {
        $bladePath = resource_path('views/admin/components/copilot-widget.blade.php');
        $this->assertFileExists($bladePath);

        $content = file_get_contents($bladePath);

        // Must include responsive width calculation and mobile max-width
        $this->assertStringContainsString('w-[calc(100vw-3rem)]', $content);
        $this->assertStringContainsString('max-w-sm', $content);
        $this->assertStringContainsString('sm:w-96', $content);

        // Must NOT use standalone fixed " w-96 " class on the panel
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\b(?<!sm:)w-96\b[^"]*"/', $content);
    }

    /**
     * @test
     * 3. Copilot diff modal container contains x-cloak attribute.
     */
    public function it_verifies_copilot_diff_modal_has_x_cloak_attribute(): void
    {
        $bladePath = resource_path('views/admin/components/copilot-diff-modal.blade.php');
        $this->assertFileExists($bladePath);

        $content = file_get_contents($bladePath);

        // Root container must have x-cloak with x-show="showDiffModal"
        $this->assertMatchesRegularExpression('/<div\s+[^>]*x-cloak[^>]*x-show="showDiffModal"/', $content);
    }

    /**
     * @test
     * 4. Rendered copilot-widget component contains responsive classes.
     */
    public function it_renders_copilot_widget_with_responsive_classes(): void
    {
        $html = View::make('admin.components.copilot-widget')->render();

        $this->assertStringContainsString('w-[calc(100vw-3rem)]', $html);
        $this->assertStringContainsString('max-w-sm', $html);
        $this->assertStringContainsString('sm:w-96', $html);
    }

    /**
     * @test
     * 5. Rendered copilot-diff-modal component contains x-cloak.
     */
    public function it_renders_copilot_diff_modal_with_x_cloak(): void
    {
        $html = View::make('admin.components.copilot-diff-modal')->render();

        $this->assertStringContainsString('x-cloak', $html);
        $this->assertStringContainsString('x-show="showDiffModal"', $html);
    }
}

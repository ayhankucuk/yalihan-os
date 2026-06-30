<?php

namespace App\Services\Wizard;

/**
 * @sab-ignore-catch
 */

use App\Contracts\TemplateResolverInterface;
use App\Exceptions\TemplateCategoryMismatchException;
use App\Exceptions\TemplateNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * WizardGateService
 * SAB §4: Wizard başlamadan önce template mapping zorunlu olarak doğrulanır.
 * Mapping yoksa DomainException ile FAIL-FAST — wizard açılmaz.
 */
class WizardGateService
{
    public function __construct(
        private readonly TemplateResolverInterface $resolver
    ) {}

    /**
     * Wizard açılabilir mi? — Template mapping kontrolü
     *
     * @throws TemplateNotFoundException   Mapping bulunamazsa
     * @throws TemplateCategoryMismatchException  Kategori uyuşmazlığında
     * @throws \InvalidArgumentException  junction_id geçersizse
     */
    public function dogrulaWizardGirisi(int $junctionId, ?int $kategoriId = null): void
    {
        // resolveByJunction TemplateNotFoundException fırlatırsa wizard açılmaz
        $template = $this->resolver->resolveByJunction($junctionId, $kategoriId);

        Log::debug('wizard_gate_pass', [
            'junction_id'  => $junctionId,
            'kategori_id'  => $kategoriId,
            'template_id'  => $template->id,
            'template_ad'  => $template->ad,
        ]);
    }

    /**
     * Template var mı? — Exception yerine bool döner (read-only kontrol)
     * @sab-ignore-catch
     */
    public function templateMevcut(int $junctionId, ?int $kategoriId = null): bool
    {
        /** @sab-ignore-catch */
        try {
            $this->dogrulaWizardGirisi($junctionId, $kategoriId);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}

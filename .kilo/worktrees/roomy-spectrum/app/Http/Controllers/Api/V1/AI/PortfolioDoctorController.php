<?php

namespace App\Http\Controllers\Api\V1\AI;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AI\Portfolio\PortfolioDoctorService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AI Portfolio Doctor Controller
 *
 * Provides endpoints for portfolio health summary and individual listing diagnostics.
 * Domain: AI / Decision Augmentation
 * Context7 Compliant: ✅
 */
class PortfolioDoctorController extends Controller
{
    public function __construct(
        private PortfolioDoctorService $doctorService
    ) {}

    /**
     * GET /api/v1/ai/portfolio/doctor/summary
     *
     * Returns a summary of the advisor's portfolio health.
     */
    public function summary(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return ResponseService::error('Oturum gerekli.', 401);
        }

        $summary = $this->doctorService->getPortfolioSummary($user);

        return ResponseService::success($summary, 'Portföy özeti başarıyla oluşturuldu.');
    }

    /**
     * GET /api/v1/ai/portfolio/doctor/problematic
     *
     * Returns a list of problematic listings in the advisor's portfolio.
     */
    public function problematic(Request $request)
    {
        $user = Auth::user();
        $threshold = $request->query('threshold', 60);

        if (!$user) {
            return ResponseService::error('Oturum gerekli.', 401);
        }

        $problematicListings = $this->doctorService->getProblematicListings($user, (int)$threshold);

        return ResponseService::success($problematicListings, 'Sorunlu ilanlar başarıyla listelendi.');
    }

    /**
     * GET /api/v1/ai/portfolio/doctor/diagnostics/{ilanId}
     *
     * Returns a detailed diagnostic report and treatment plan for a specific listing.
     */
    public function diagnostics(int $ilanId)
    {
        $user = Auth::user();
        $ilan = Ilan::findOrFail($ilanId);

        // Security Check: Only own listings
        if ($ilan->danisman_id !== $user->id && !$user->is_admin) {
            return ResponseService::error('Bu ilan için yetkiniz yok.', 403);
        }

        $report = $this->doctorService->getDiagnosticReport($ilan);

        return ResponseService::success($report, 'İlan teşhis raporu başarıyla oluşturuldu.');
    }
}

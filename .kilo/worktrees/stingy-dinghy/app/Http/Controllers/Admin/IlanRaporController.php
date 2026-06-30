<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\ReportService;
use App\Actions\Admin\Ilan\RefreshIlanRaporAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * İlan Rapor Controller
 *
 * [YALIHAN_REPORTING_0206]
 * Mühürlü PDF raporlarını stream eder ve yeniden üretir
 */
class IlanRaporController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Show/Download mühürlü rapor
     *
     * Public endpoint with Signed URL protection
     * NO auth required - signature is the authorization
     *
     * @param Request $request
     * @param Ilan $ilan
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function show(Request $request, Ilan $ilan)
    {
        // 1. Hash check (Prevent horizontal tampering if signed URL is leaked but with wrong hash)
        if ($request->hash !== $ilan->rapor_hash) {
            abort(404, 'Report hash mismatch.');
        }

        // 2. Validity check (Gone if invalidated)
        if ($ilan->rapor_gecersiz_mi) {
            abort(410, 'This report version is gone.');
        }

        // 3. File existence check
        if (!Storage::disk('local')->exists($ilan->rapor_yolu)) {
            abort(404, 'Report file not found on disk.');
        }

        // Audit log (without user_id since guest can access)
        Log::info('[YALIHAN_REPORTING] Rapor indirildi', [
            'ilan_id' => $ilan->id,
            'rapor_hash' => $ilan->rapor_hash,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $filePath = storage_path('app/' . $ilan->rapor_yolu);

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="YALIHAN_REPORT_' . $ilan->id . '.pdf"',
        ]);
    }

    /**
     * Refresh/Regenerate rapor
     *
     * Auth + Policy protected
     *
     * @param Ilan $ilan
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refresh(Ilan $ilan, Request $request)
    {
        $this->authorize('viewIlanRaporu', $ilan);

        $locale = $request->input('locale', 'tr');

        try {
            $result = $this->reportService->regenerate($ilan, $locale);

            if ($result['success']) {
                // Update ilan columns
                app(RefreshIlanRaporAction::class)->handle($ilan, $result, auth()->id(), $locale);

                Log::info('[YALIHAN_REPORTING] Rapor yenilendi', [
                    'ilan_id' => $ilan->id,
                    'user_id' => auth()->id(),
                    'locale' => $locale,
                ]);

                return redirect()->back()->with('success', 'Rapor başarıyla yenilendi.');
            } else {
                return redirect()->back()->with('error', 'Rapor oluşturulamadı: ' . ($result['metadata']['error'] ?? 'Bilinmeyen hata'));
            }
        } catch (\Exception $e) {
            Log::error('[YALIHAN_REPORTING] Rapor yenileme hatası', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Rapor yenilenirken hata oluştu.');
        }
    }

    /**
     * Generate signed URL for rapor
     * Helper method (can be called from other controllers)
     *
     * @param Ilan $ilan
     * @param int $hours
     * @return string
     */
    public static function generateSignedUrl(Ilan $ilan, int $hours = 24): string
    {
        if (!$ilan->rapor_hash) {
            throw new \Exception('Rapor henüz oluşturulmamış.');
        }

        return URL::temporarySignedRoute(
            'rapor.download',  // ✅ New public route name
            now()->addHours($hours),
            [
                'ilan' => $ilan->id,
                'hash' => $ilan->rapor_hash,
            ]
        );
    }
}

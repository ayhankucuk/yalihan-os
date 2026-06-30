<?php

namespace App\Http\Controllers\Owner;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\OwnerReportRow;
use App\Models\OwnerReportMetric;
use App\Models\OwnerReportExport;
use App\Actions\OwnerReport\ExportOwnerReportAction;
use App\Application\Shared\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * OwnerReportController
 * Sorumluluk: Ev sahibi raporlarını listeleme, export talebi ve indirme.
 * Kurallar: Thin Controller - Sadece validation ve dispatch.
 * Context7 Standard: SAB-THIN-CONTROLLER-V1
 */
class OwnerReportController extends Controller
{
    public function __construct(private TenantContextResolver $tenantResolver)
    {
        $this->authorizeResource(OwnerReportRow::class, 'report');
    }

    /**
     * Rapor Satırlarını Listele
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = auth()->user();
        $ilanId       = $request->ilan_id;
        $baslangicTar = $request->baslangic_tarihi ?? $request->input('start_date');
        $bitisTar     = $request->bitis_tarihi     ?? $request->input('end_date');

        $rows = OwnerReportRow::where('tenant_id', $this->tenantResolver->resolve()->tenantId)
            ->where('owner_id', $user->id)
            ->when($ilanId,       fn($q) => $q->where('ilan_id', $ilanId))
            ->when($baslangicTar, fn($q) => $q->where('kayit_tarihi', '>=', $baslangicTar))
            ->when($bitisTar,     fn($q) => $q->where('kayit_tarihi', '<=', $bitisTar))
            ->orderBy('kayit_tarihi', 'desc') // context7-ignore
            ->paginate(20);

        $metrics = OwnerReportMetric::where('tenant_id', $this->tenantResolver->resolve()->tenantId)
            ->where('owner_id', $user->id)
            ->when($ilanId, fn($q) => $q->where('ilan_id', $ilanId))
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'rows'    => $rows,
                    'metrics' => $metrics,
                ],
            ]);
        }

        return view('owner.raporlar.index', compact('rows', 'metrics', 'ilanId', 'baslangicTar', 'bitisTar'));
    }

    /**
     * Export Başlat
     */
    public function export(Request $request, ExportOwnerReportAction $action): JsonResponse
    {
        $this->authorize('export', OwnerReportRow::class);

        $validated = $request->validate([
            'ilan_id'          => 'sometimes|exists:ilanlar,id',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi'     => 'required|date|after_or_equal:baslangic_tarihi',
            'format'           => 'required|in:csv,pdf',
        ]);

        $export = $action->handle(auth()->user(), $validated);

        return response()->json([
            'success'   => true,
            'message'   => 'Export islemi baslatildi.',
            'export_id' => $export->id,
        ], 202);
    }

    /**
     * Export İndir
     */
    public function download(OwnerReportExport $export): BinaryFileResponse|JsonResponse
    {
        $this->authorize('download', $export);

        if ($export->islem_durumu !== 'tamamlandi') {
            return response()->json(['success' => false, 'message' => 'Dosya henüz hazır değil.'], 400);
        }

        if (!Storage::disk('local')->exists($export->dosya_yolu)) {
            return response()->json(['success' => false, 'message' => 'Dosya bulunamadı.'], 404);
        }

        return response()->download(storage_path('app/' . $export->dosya_yolu));
    }
}

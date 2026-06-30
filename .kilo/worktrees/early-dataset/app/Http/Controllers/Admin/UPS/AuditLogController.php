<?php

namespace App\Http\Controllers\Admin\UPS;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\TemplateChangeLog;
use App\Models\YayinTipiSablonu;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Audit Log Controller
 *
 * Template sistemi değişiklikleri izler ve görüntüler
 *
 * Context7 Compliant: ✅
 */
class AuditLogController extends Controller
{
    /**
     * Audit log listesi
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Filtreleme
        $query = TemplateChangeLog::with(['template.kategori', 'user']);

        // Template filtreleme (V2: yayin_tipi_sablonu_id)
        if ($request->has('template_id') && $request->template_id) {
            $query->where('yayin_tipi_sablonu_id', $request->template_id);
        }

        // User filtreleme
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Action filtreleme
        if ($request->has('aksiyon_tipi') && $request->aksiyon_tipi) {
            $query->where('aksiyon_tipi', $request->aksiyon_tipi);
        }

        // Tarih aralığı
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sıralama (en yeni önce)
        $logs = $query->orderByDesc('created_at')->paginate(50); // context7-ignore

        // Templates (dropdown için) - Get only those with categorizations
        $templates = YayinTipiSablonu::with('kategori')
            ->get()
            ->sortBy('kategori.name');

        return view('admin.ups.audit-log.index', compact('logs', 'templates'));
    }

    /**
     * Tekil log detayı
     *
     * @param TemplateChangeLog $auditLog
     * @return \Illuminate\View\View
     */
    public function show(TemplateChangeLog $auditLog)
    {
        return view('admin.ups.audit-log.show', compact('auditLog'));
    }

    /**
     * Log'u sil
     *
     * @param TemplateChangeLog $auditLog
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TemplateChangeLog $auditLog)
    {
        $auditLog->delete();

        return redirect()->back()
            ->with('success', 'Audit log kaydı silindi');
    }

    /**
     * Logs'ları export (CSV)
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request): StreamedResponse
    {
        $query = TemplateChangeLog::with(['template.kategori', 'user']);

        // Aynı filtreleri uygula (V2: yayin_tipi_sablonu_id)
        if ($request->has('template_id') && $request->template_id) {
            $query->where('yayin_tipi_sablonu_id', $request->template_id);
        }
        if ($request->has('aksiyon_tipi') && $request->aksiyon_tipi) {
            $query->where('aksiyon_tipi', $request->aksiyon_tipi);
        }
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderByDesc('created_at')->get(); // context7-ignore

        // CSV üret
        $headers = [
            'Content-Encoding' => 'UTF-8',
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit-log-' . now()->format('Y-m-d-His') . '.csv"',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Template', 'User', 'Action', 'Description', 'Old Values', 'New Values', 'Date'], ',');

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->template?->kategori?->name ?? '-',
                    $log->user?->name ?? '-',
                    $log->aksiyon_tipi,
                    $log->aciklama ?? '-',
                    json_encode($log->eski_degerler) ?? '-',
                    json_encode($log->yeni_degerler) ?? '-',
                    $log->created_at->format('d.m.Y H:i:s'),
                ], ',');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Eski log'ları sil (cleanup)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cleanup()
    {
        // 90 günden eski logları sil
        $days = request()->input('days', 90);

        $deleted = TemplateChangeLog::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()->back()
            ->with('success', "{$deleted} eski log kaydı silindi");
    }
}

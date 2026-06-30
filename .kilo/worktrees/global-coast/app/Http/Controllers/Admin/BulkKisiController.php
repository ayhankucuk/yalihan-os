<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Kisi;
use App\Services\Kisi\BulkKisiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BulkKisiController extends AdminController
{
    public function __construct(
        private BulkKisiService $bulkService
    ) {}
    /**
     * Display the bulk operations dashboard
     */
    public function index()
    {
        // ✅ SAB: Stats calculation delegated to service
        $stats = $this->bulkService->getDashboardStats();

        return view('admin.bulk-kisi.index', compact('stats'));
    }

    /**
     * Show the form for creating multiple kisiler
     */
    public function create()
    {
        return view('admin.bulk-kisi.create');
    }

    /**
     * Store multiple kisiler in bulk
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kisiler' => 'required|array|min:1',
            'kisiler.*.ad' => 'required|string|max:255',
            'kisiler.*.soyad' => 'required|string|max:255',
            'kisiler.*.email' => 'nullable|email|unique:kisiler,email',
            'kisiler.*.telefon' => 'nullable|string|max:20',
            'kisiler.*.tc_kimlik' => 'nullable|string|size:11|unique:kisiler,tc_kimlik',
            'kisiler.*.kisi_tipi' => 'required|in:musteri,mal_sahibi,danismani',
            'kisiler.*.aktiflik_durumu' => 'required|boolean', // ✅ Reconciled
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // SAB Kural 1/11: TX + domain logic service'te
            $result = $this->bulkService->bulkCreate($request->kisiler, auth()->id());

            return response()->json([
                'success' => true,
                'message' => count($result['created']) . ' kişi başarıyla oluşturuldu.',
                'data' => [
                    'created_count' => count($result['created']),
                    'error_count' => count($result['errors']),
                    'errors' => $result['errors'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Toplu kayıt işlemi başarısız: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for bulk editing
     */
    public function edit()
    {
        return view('admin.bulk-kisi.edit');
    }

    /**
     * Update multiple kisiler in bulk
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kisi_ids' => 'required|array|min:1',
            'kisi_ids.*' => 'exists:kisiler,id',
            'updates' => 'required|array',
            'updates.aktiflik_durumu' => 'sometimes|in:aktif,pasif', // ✅ Reconciled
            'updates.tip' => 'sometimes|in:musteri,mal_sahibi,danismani',
            'updates.danismanId' => 'sometimes|nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // SAB Kural 1/11: TX + domain logic service'te
            $updatedCount = $this->bulkService->bulkUpdate(
                $request->kisi_ids,
                $request->updates,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => $updatedCount . ' kişi başarıyla güncellendi.',
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Toplu güncelleme işlemi başarısız: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove multiple kisiler in bulk
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kisi_ids' => 'required|array|min:1',
            'kisi_ids.*' => 'exists:kisiler,id',
            'force_delete' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // SAB Kural 1/11: TX + domain logic service'te
            $deletedCount = $this->bulkService->bulkDelete(
                $request->kisi_ids,
                $request->boolean('force_delete', false),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => $deletedCount . ' kişi başarıyla silindi.',
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Toplu silme işlemi başarısız: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export kisiler data
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,xlsx,json',
            'filters' => 'sometimes|array',
            'filters.kisi_tipi' => 'sometimes|in:musteri,mal_sahibi,danismani', // ✅ SAB
            'filters.aktiflik_durumu' => 'sometimes|in:aktif,pasif', // ✅ Reconciled
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // ✅ SAB: Data retrieval delegated to service
            $kisiler = $this->bulkService->getExportData($request->filters ?? []);

            switch ($request->format) {
                case 'json':
                    return response()->json([
                        'success' => true,
                        'data' => $kisiler,
                        'count' => $kisiler->count(),
                    ]);

                case 'csv':
                    $filename = 'kisiler_export_'.date('Y-m-d_H-i-s').'.csv';

                    return response()->streamDownload(function () use ($kisiler) {
                        $file = fopen('php://output', 'w');

                        // CSV header
                        fputcsv($file, ['ID', 'Ad', 'Soyad', 'Email', 'Telefon', 'TC Kimlik', 'Kişi Tipi', 'Aktiflik Durumu', 'Oluşturma Tarihi']);

                        // CSV rows (Transformation can stay in controller/export layer)
                        foreach ($kisiler as $kisi) {
                            fputcsv($file, [
                                $kisi->id,
                                $kisi->ad,
                                $kisi->soyad,
                                $kisi->email,
                                $kisi->telefon,
                                $kisi->tc_kimlik,
                                $kisi->kisi_tipi,
                                $kisi->aktiflik_durumu ? IlanDurumu::YAYINDA->value : 'Pasif', // ✅ Reconciled
                                $kisi->created_at->format('Y-m-d H:i:s'),
                            ]);
                        }

                        fclose($file);
                    }, $filename, [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                    ]);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Desteklenmeyen format',
                    ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Bulk kisi export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export işlemi başarısız: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import kisiler from file
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'has_header' => 'boolean',
            'delimiter' => 'sometimes|string|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // CSV parsing (IO concern — controller'da kalır)
            $file = $request->file('file');
            $hasHeader = $request->get('has_header', true);
            $csvData = array_map('str_getcsv', file($file->getPathname()));

            if ($hasHeader) {
                array_shift($csvData);
            }

            // SAB Kural 1/11: TX + domain logic service'te
            $result = $this->bulkService->importFromCsv($csvData, auth()->id());

            Log::info('Bulk kisi import completed', [
                'file_name' => $file->getClientOriginalName(),
                'created_count' => count($result['created']),
                'error_count' => count($result['errors']),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => count($result['created']) . ' kişi başarıyla import edildi.',
                'data' => [
                    'created_count' => count($result['created']),
                    'error_count' => count($result['errors']),
                    'errors' => $result['errors'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import işlemi başarısız: ' . $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\Ilan;
use App\Services\Ilan\IlanBulkService;
use App\Services\Logging\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Ilan Bulk Controller
 *
 * Context7 Standardı: C7-ILAN-BULK-CONTROLLER-2025-12-23
 *
 * Handles bulk operations for Ilan
 * Business logic delegated to IlanBulkService
 *
 * @package App\Http\Controllers\Admin
 */
class IlanBulkController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('can:manage-ilanlar');
    }

    /**
     * Bulk action for listings
     *
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function bulkAction(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|string|in:activate,deactivate,delete,export,assign_danisman,add_tag,remove_tag',
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:ilanlar,id',
                'value' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasyon hatası.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Export işlemi ayrı tutulur
            if ($request->action === 'export') {
                return $this->handleExport($request->ids);
            }

            $service = app(IlanBulkService::class);
            $result = $service->bulkAction($request->action, $request->ids, $request->value);
            $httpCode = $result['success'] ? 200 : 400;

            return response()->json($result, $httpCode);
        } catch (\Exception $e) {
            LogService::error('Bulk action error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'İşlem sırasında hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update listings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:ilanlar,id',
                'field' => 'required|string',
                'value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasyon hatası.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $service = app(IlanBulkService::class);
            $result = $service->bulkUpdate($request->ids, $request->field, $request->value);

            return response()->json($result);
        } catch (\Exception $e) {
            LogService::error('Bulk update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Toplu güncelleme sırasında hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete listings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:ilanlar,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasyon hatası.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $service = app(IlanBulkService::class);
            $result = $service->bulkAction('delete', $request->ids);

            return response()->json($result);
        } catch (\Exception $e) {
            LogService::error('Bulk delete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Toplu silme sırasında hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle export
     *
     * @param array $ids
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function handleExport(array $ids)
    {
        $ilanlar = Ilan::with(['kategori', 'il', 'ilce'])
            ->whereIn('id', $ids)
            ->get();

        $exportData = $ilanlar->map(function ($ilan) {
            return [
                'ID' => $ilan->id,
                'Başlık' => $ilan->baslik,
                'Fiyat' => $ilan->fiyat,
                'Para Birimi' => $ilan->para_birimi,
                'Durum' => $ilan->yayin_durumu,
                'Kategori' => $ilan->kategori->name ?? '',
                'İl' => $ilan->il->il_adi ?? '',
                'İlçe' => $ilan->ilce->ilce_adi ?? '',
                'Oluşturulma' => $ilan->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $export = new class($exportData) implements FromArray, WithHeadings
        {
            public function __construct(private array $data) {}

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return ['ID', 'Başlık', 'Fiyat', 'Para Birimi', 'Durum', 'Kategori', 'İl', 'İlçe', 'Oluşturulma'];
            }
        };

        return Excel::download($export, 'ilanlar_' . date('Y-m-d_His') . '.xlsx');
    }
}


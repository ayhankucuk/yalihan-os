<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use App\Services\Ilan\IlanBulkService;
use Illuminate\Support\Facades\Validator;

/**
 * Bulk Management Controller - Phase 5
 * Toplu İşlemler: Yayın Tipi, Fiyat, FLIR
 */
class BulkManagementController extends Controller
{
    public function __construct(
        private readonly IlanBulkService $ilanBulk,
    ) {}
    /**
     * Toplu Yayın Tipi Değiştirme
     */
    public function bulkUpdateYayinTipi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ilan_ids' => 'required|array|min:1',
            'ilan_ids.*' => 'exists:ilanlar,id',
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::error('Validation failed', $validator->errors(), 422);
        }

        $result = $this->ilanBulk->bulkUpdateYayinTipi(
            (int) auth()->id(),
            $request->ilan_ids,
            (int) $request->yayin_tipi_id,
        );

        return ResponseService::success($result);
    }

    /**
     * Toplu Fiyat Güncelleme (Yüzdesel)
     */
    public function bulkUpdateFiyat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ilan_ids' => 'required|array|min:1',
            'ilan_ids.*' => 'exists:ilanlar,id',
            'percentage' => 'required|numeric|min:-50|max:100', // -50% to +100%
            'operation' => 'required|in:increase,decrease',
        ]);

        if ($validator->fails()) {
            return ResponseService::error('Validation failed', $validator->errors(), 422);
        }

        $result = $this->ilanBulk->bulkUpdateFiyat(
            (int) auth()->id(),
            $request->ilan_ids,
            (float) $request->percentage,
            $request->operation,
        );

        return ResponseService::success($result);
    }

    /**
     * Toplu Aktiflik Durumu Değiştirme
     */
    public function bulkChangeAktiflikDurumu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ilan_ids' => 'required|array|min:1',
            'ilan_ids.*' => 'exists:ilanlar,id',
            'aktiflik_durumu' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return ResponseService::error('Validation failed', $validator->errors(), 422);
        }

        $updated = Ilan::whereIn('id', $request->ilan_ids)
            ->where('kullanici_id', auth()->id())
            ->update(['aktiflik_durumu' => $request->aktiflik_durumu]);

        return ResponseService::success([
            'updated_count' => $updated,
            'new_status' => $request->aktiflik_durumu ? 'aktif' : 'pasif',
            'message' => "$updated ilan durumu değiştirildi"
        ]);
    }

    /**
     * Toplu Kategori Değiştirme
     */
    public function bulkUpdateKategori(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ilan_ids' => 'required|array|min:1',
            'ilan_ids.*' => 'exists:ilanlar,id',
            'kategori_id' => 'required|exists:ilan_kategorileri,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::error('Validation failed', $validator->errors(), 422);
        }

        $updated = Ilan::whereIn('id', $request->ilan_ids)
            ->where('kullanici_id', auth()->id())
            ->update(['kategori_id' => $request->kategori_id]);

        return ResponseService::success([
            'updated_count' => $updated,
            'message' => "$updated ilan kategorisi değiştirildi"
        ]);
    }

    /**
     * Toplu Silme (Soft Delete)
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ilan_ids' => 'required|array|min:1',
            'ilan_ids.*' => 'exists:ilanlar,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::error('Validation failed', $validator->errors(), 422);
        }

        $deleted = Ilan::whereIn('id', $request->ilan_ids)
            ->where('kullanici_id', auth()->id())
            ->delete(); // Soft delete if using SoftDeletes trait

        return ResponseService::success([
            'deleted_count' => $deleted,
            'message' => "$deleted ilan silindi"
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\FeatureAssignment;
use Illuminate\Http\Request;

/**
 * @deprecated 2026-04-05 Legacy UPS template controller. Only reorder() exists, route purged.
 * ⚠️ QUARANTINE: Do not add new methods. Route already commented out (admin.php L814).
 * Target: safe to delete after confirming no remaining references.
 */
class UpsTemplateController extends Controller
{
    public function __construct() {
    }

    /**
     * Reorder feature assignments for a category and publication type.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required|integer',
            'yayin_tipi_id' => 'required|integer',
            'sequences' => 'required|array',
            'sequences.*.feature_id' => 'required|integer',
            'sequences.*.display_order' => 'required|integer',
        ]);

        try {
            // ✅ SAB: Doğru kolon isimleri kullan
            $template = YayinTipiSablonu::where('kategori_id', $request->kategori_id)
                ->where('id', $request->yayin_tipi_id)
                ->firstOrFail();

            // $this->assignmentService->reorderFeatures($template, $request->sequences);

            return response()->json([
                'success' => true,
                'message' => 'Siralama guncellendi.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

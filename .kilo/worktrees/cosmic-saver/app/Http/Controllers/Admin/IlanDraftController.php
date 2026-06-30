<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Ilan\IlanTaslakService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * IlanDraftController (Database SSOT)
 * Sprint Plan A4 Implementation
 */
class IlanDraftController extends Controller
{
    protected IlanTaslakService $taslakService;

    public function __construct(IlanTaslakService $taslakService)
    {
        $this->taslakService = $taslakService;
        $this->middleware('can:manage-ilanlar');
    }

    /**
     * Get or create active draft
     * GET /admin/ilanlar/draft/active
     */
    public function active(Request $request)
    {
        try {
            $user = $request->user();
            $siteId = $request->get('site_id');

            $draft = $this->taslakService->getOrCreateActiveDraft($user, $siteId);

            return response()->json([
                'success' => true,
                'data' => $draft
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Aktif taslak alınamadı: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load specific draft
     * GET /admin/ilanlar/draft/{id}
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $draft = $this->taslakService->loadDraft($id, $user);

            if (!$draft) {
                return response()->json(['success' => false, 'message' => 'Taslak bulunamadı'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $draft
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Taslak yüklenemedi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save draft payload and step (debounced from frontend)
     * PATCH /admin/ilanlar/draft/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $payload = $request->input('payload', []);
            $step = $request->input('step', 1);

            $this->taslakService->saveDraft($id, $payload, (int)$step);

            return response()->json([
                'success' => true,
                'message' => 'Taslak kaydedildi'
            ]);
        } catch (\Exception $e) {
            $code = $e->getMessage() === "Payload exceeds 256KB limit." ? 413 : 500;
            return response()->json([
                'success' => false,
                'message' => 'Taslak kaydedilemedi: ' . $e->getMessage()
            ], $code);
        }
    }

    /**
     * Commit draft to real listing
     * POST /admin/ilanlar/draft/{id}/commit
     */
    public function commit(Request $request, $id)
    {
        try {
            $ilan = $this->taslakService->commitDraftToIlan($id);

            return response()->json([
                'success' => true,
                'message' => 'İlan oluşturuldu ve taslak kapatıldı',
                'data' => [
                    'ilan_id' => $ilan->id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commit işlemi başarısız: ' . $e->getMessage()
            ], 500);
        }
    }

    public function save(Request $request)
    {
        try {
            $user = $request->user();
            $siteId = $request->input('site_id');
            $payload = (array) $request->input('payload', []);
            $step = (int) $request->input('step', 1);

            $draft = $this->taslakService->getOrCreateActiveDraft($user, $siteId);
            $this->taslakService->saveDraft($draft->id, $payload, $step);

            return response()->json([
                'success' => true,
                'data' => [
                    'draft_id' => $draft->id,
                    'step' => $step,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Taslak kaydedilemedi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function load(Request $request)
    {
        try {
            $user = $request->user();
            $siteId = $request->input('site_id');
            $draft = $this->taslakService->getOrCreateActiveDraft($user, $siteId);

            return response()->json([
                'success' => true,
                'data' => $draft,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Taslak yüklenemedi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function clear(Request $request)
    {
        try {
            $user = $request->user();
            $siteId = $request->input('site_id');
            $draft = $this->taslakService->getOrCreateActiveDraft($user, $siteId);
            $this->taslakService->closeDraft($draft->id);

            return response()->json([
                'success' => true,
                'message' => 'Taslak temizlendi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Taslak temizlenemedi: ' . $e->getMessage(),
            ], 500);
        }
    }
}

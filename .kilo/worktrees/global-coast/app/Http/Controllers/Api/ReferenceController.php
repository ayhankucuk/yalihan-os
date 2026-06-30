<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\IlanReferansService;
use App\Services\Logging\LogService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;

/**
 * Reference & File Management API Controller
 *
 * Context7 Standardı: C7-REF-API-2025-11-05
 *
 * Endpoint'ler:
 * - Ref numarası oluşturma/güncelleme
 * - Basename oluşturma/güncelleme
 * - Portal numarası yönetimi
 * - Klasör yapısı yönetimi
 * - Audit log görüntüleme
 * - Revizyon geçmişi
 */
class ReferenceController extends Controller
{
    use ValidatesApiRequests;

    protected $referansService;

    public function __construct(IlanReferansService $referansService)
    {
        $this->referansService = $referansService;
    }

    /**
     * Ref numarası oluştur (ilan için)
     *
     * POST /api/reference/generate
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateRef(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'region_code' => 'nullable|string|max:10',
            'type_code' => 'nullable|string|max:5', // context7-ignore
            'force' => 'nullable|boolean',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($request->ilan_id);

            // Eğer ref numarası varsa ve force=false ise, hata döndür
            if ($ilan->referans_no && ! $request->boolean('force')) {
                return ResponseService::error('Bu ilan için zaten ref numarası mevcut. Force=true ile yeniden oluşturabilirsiniz.', 400);
            }

            // Ref numarası oluştur
            $referansNo = $this->referansService->generateReferansNo($ilan);

            // Benzersizlik kontrolü
            if (! $this->referansService->isUnique($referansNo, $ilan->id)) {
                // Retry mekanizması (3 deneme)
                for ($i = 0; $i < 3; $i++) {
                    $referansNo = $this->referansService->generateReferansNo($ilan);
                    if ($this->referansService->isUnique($referansNo, $ilan->id)) {
                        break;
                    }
                }
            }

            // İlan'a kaydet
            $ilan->update(['referans_no' => $referansNo]);

            LogService::info('Ref numarası oluşturuldu', [
                'ilan_id' => $ilan->id,
                'referans_no' => $referansNo,
                'user_id' => auth()->id(),
            ]);

            return ResponseService::success([
                'referans_no' => $referansNo,
                'ilan_id' => $ilan->id,
                'message' => 'Ref numarası başarıyla oluşturuldu',
            ]);
        } catch (\Exception $e) {
            LogService::error('Ref numarası oluşturma hatası', [
                'ilan_id' => $request->ilan_id,
                'error' => $e->getMessage(),
            ], $e);

            return ResponseService::serverError('Ref numarası oluşturulurken hata oluştu', $e);
        }
    }

    /**
     * Ref numarası doğrula
     *
     * GET /api/reference/validate/{referansNo}
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateRef(string $referansNo)
    {
        try {
            $ilan = $this->referansService->findByReferansNo($referansNo);

            if (! $ilan) {
                return ResponseService::error('Ref numarası bulunamadı', 404);
            }

            return ResponseService::success([
                'valid' => true,
                'referans_no' => $referansNo,
                'ilan_id' => $ilan->id,
                'baslik' => $ilan->baslik,
                'yayin_durumu' => $ilan->yayin_durumu,
            ]);
        } catch (\Exception $e) {
            return ResponseService::serverError('Ref numarası doğrulanırken hata oluştu', $e);
        }
    }

    /**
     * Basename oluştur/güncelle
     *
     * POST /api/reference/basename
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateBasename(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'format' => 'nullable|in:full,short',
            'include_owner' => 'nullable|boolean',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($request->ilan_id);

            // Basename oluştur
            if ($request->input('format') === 'short') {
                $basename = $this->referansService->generateKisaDosyaAdi($ilan);
            } else {
                $basename = $this->referansService->generateDosyaAdi($ilan);
            }

            // İlan'a kaydet
            $ilan->update(['dosya_adi' => $basename]);

            LogService::info('Basename oluşturuldu', [
                'ilan_id' => $ilan->id,
                'basename' => $basename,
                'format' => $request->input('format', 'full'),
                'user_id' => auth()->id(),
            ]);

            return ResponseService::success([
                'basename' => $basename,
                'ilan_id' => $ilan->id,
                'format' => $request->input('format', 'full'),
                'message' => 'Basename başarıyla oluşturuldu',
            ]);
        } catch (\Exception $e) {
            LogService::error('Basename oluşturma hatası', [
                'ilan_id' => $request->ilan_id,
                'error' => $e->getMessage(),
            ], $e);

            return ResponseService::serverError('Basename oluşturulurken hata oluştu', $e);
        }
    }

    /**
     * Portal numarası güncelle
     *
     * POST /api/reference/portal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePortalNumber(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|exists:ilanlar,id',
            'portal' => 'required|in:sahibinden,emlakjet,hepsiemlak,zingat,hurriyetemlak',
            'portal_id' => 'required|string|max:100',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($request->ilan_id);
            $portal = $request->portal;
            $portalId = $request->portal_id;

            // Portal ID alanını güncelle
            $fieldName = $portal.'_id';
            $ilan->update([$fieldName => $portalId]);

            LogService::info('Portal numarası güncellendi', [
                'ilan_id' => $ilan->id,
                'portal' => $portal,
                'portal_id' => $portalId,
                'user_id' => auth()->id(),
            ]);

            return ResponseService::success([
                'ilan_id' => $ilan->id,
                'portal' => $portal,
                'portal_id' => $portalId,
                'message' => 'Portal numarası başarıyla güncellendi',
            ]);
        } catch (\Exception $e) {
            LogService::error('Portal numarası güncelleme hatası', [
                'ilan_id' => $request->ilan_id,
                'portal' => $request->portal,
                'error' => $e->getMessage(),
            ], $e);

            return ResponseService::serverError('Portal numarası güncellenirken hata oluştu', $e);
        }
    }

    /**
     * İlan için ref ve basename bilgilerini getir
     *
     * GET /api/reference/{ilanId}
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReferenceInfo(int $ilanId)
    {
        try {
            $ilan = Ilan::findOrFail($ilanId);

            return ResponseService::success([
                'ilan_id' => $ilan->id,
                'referans_no' => $ilan->referans_no,
                'dosya_adi' => $ilan->dosya_adi,
                'portal_numbers' => [
                    'sahibinden' => $ilan->sahibinden_id,
                    'emlakjet' => $ilan->emlakjet_id,
                    'hepsiemlak' => $ilan->hepsiemlak_id,
                    'zingat' => $ilan->zingat_id,
                    'hurriyetemlak' => $ilan->hurriyetemlak_id,
                ],
                'baslik' => $ilan->baslik,
                'yayin_durumu' => $ilan->yayin_durumu,
            ]);
        } catch (\Exception $e) {
            return ResponseService::serverError('Ref bilgileri alınırken hata oluştu', $e);
        }
    }

    /**
     * Toplu ref numarası oluştur (batch)
     *
     * POST /api/reference/batch-generate
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchGenerateRef(Request $request)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_ids' => 'required|array|min:1',
            'ilan_ids.*' => 'required|exists:ilanlar,id',
            'force' => 'nullable|boolean',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $ilanIds = $request->ilan_ids;
            $force = $request->boolean('force');
            $results = [
                'success' => 0,
                'failed' => 0,
                'skipped' => 0,
                'errors' => [],
            ];

            // ✅ PERFORMANCE FIX: N+1 query önlendi - Tüm ilanları tek query'de al
            $ilanlar = Ilan::whereIn('id', $ilanIds)->get()->keyBy('id');

            // ✅ PERFORMANCE FIX: Bulk update için hazırlık
            $updates = [];

            foreach ($ilanIds as $ilanId) {
                try {
                    $ilan = $ilanlar->get($ilanId);
                    if (! $ilan) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'ilan_id' => $ilanId,
                            'error' => 'İlan bulunamadı',
                        ];

                        continue;
                    }

                    // Eğer ref varsa ve force=false ise, skip et
                    if ($ilan->referans_no && ! $force) {
                        $results['skipped']++;

                        continue;
                    }

                    // Ref oluştur
                    $referansNo = $this->referansService->generateReferansNo($ilan);

                    // Benzersizlik kontrolü
                    if (! $this->referansService->isUnique($referansNo, $ilan->id)) {
                        // Retry
                        for ($i = 0; $i < 3; $i++) {
                            $referansNo = $this->referansService->generateReferansNo($ilan);
                            if ($this->referansService->isUnique($referansNo, $ilan->id)) {
                                break;
                            }
                        }
                    }

                    $updates[$ilan->id] = $referansNo;
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'ilan_id' => $ilanId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // ✅ PERFORMANCE FIX: Bulk update (CASE WHEN ile)
            if (! empty($updates)) {
                $cases = [];
                $bindings = [];
                $ids = [];
                foreach ($updates as $id => $referansNo) {
                    $cases[] = 'WHEN ? THEN ?';
                    $bindings[] = $id;
                    $bindings[] = $referansNo;
                    $ids[] = $id;
                }
                $idsPlaceholder = implode(',', array_fill(0, count($ids), '?'));
                $casesSql = implode(' ', $cases);

                \Illuminate\Support\Facades\DB::statement(
                    "UPDATE ilanlar
                     SET referans_no = CASE id {$casesSql} END
                     WHERE id IN ({$idsPlaceholder})",
                    array_merge($bindings, $ids)
                );
            }

            LogService::info('Toplu ref numarası oluşturuldu', [
                'total' => count($ilanIds),
                'results' => $results,
                'user_id' => auth()->id(),
            ]);

            return ResponseService::success([
                'total' => count($ilanIds),
                'results' => $results,
                'message' => 'Toplu ref numarası oluşturma tamamlandı',
            ]);
        } catch (\Exception $e) {
            return ResponseService::serverError('Toplu ref numarası oluşturulurken hata oluştu', $e);
        }
    }
}

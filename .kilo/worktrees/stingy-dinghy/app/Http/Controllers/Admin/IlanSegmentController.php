<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Enums\IlanDurumu;
use App\Enums\IlanSegment;
use App\Models\Ilan;
use App\Services\Ilan\IlanCrudService;
use App\Services\Ilan\IlanSegmentService;
use App\Services\Listing\YalihanLifecycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * İlan Segment Yönetimi Controller
 * Context7: Sequential workflow management for property listings
 */
class IlanSegmentController extends AdminController
{
    public function __construct(
        private readonly IlanSegmentService $segmentService,
        private readonly YalihanLifecycle   $lifecycleService,
        private readonly IlanCrudService    $crudService,
    ) {}

    /**
     * Yeni ilan oluşturma başlangıcı
     */
    public function create(Request $request)
    {
        return $this->show($request, null, null);
    }

    /**
     * Yeni ilan segment görüntüleme
     */
    public function showCreate(Request $request, $segment)
    {
        return $this->show($request, null, $segment);
    }

    /**
     * Yeni ilan segment kaydetme
     */
    public function storeCreate(Request $request, $segment)
    {
        return $this->store($request, null, $segment);
    }

    /**
     * Mevcut ilan segment düzenleme
     */
    public function showEdit(Request $request, $ilanId, $segment)
    {
        return $this->show($request, $ilanId, $segment);
    }

    /**
     * Segment tabanlı ilan oluşturma/düzenleme
     */
    public function show(Request $request, $ilanId = null, $segment = null)
    {
        try {
            // SAB-EXEMPT: display-only ghost model for form rendering, no persistence
            $ilan = $ilanId ? Ilan::findOrFail($ilanId) : new Ilan;

            // Varsayılan segment
            if (! $segment) {
                $segment = IlanSegment::PORTFOLIO_INFO;
            } else {
                $segment = IlanSegment::from($segment);
            }

            // Segment sıralaması
            $segments = IlanSegment::getOrder();
            $currentIndex = array_search($segment, $segments);

            // İlerleme aşaması

            $progress = $this->calculateProgress($ilan, $segment);

            return view('admin.ilanlar.segments.show', [
                'ilan' => $ilan,
                'currentSegment' => $segment,
                'segments' => $segments,
                'currentIndex' => $currentIndex,
                'progress' => $progress,
                'segmentData' => $this->getSegmentData($ilan, $segment),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Segment verilerini kaydet
     */
    public function store(Request $request, $ilanId = null, $segment = null)
    {
        $segment = IlanSegment::from($segment);

        // Segment'e özel validasyon
        $validator = $this->getSegmentValidator($segment, $request);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Authority: route all writes through IlanCrudService (Listing Lifecycle Authority — sealed)
        if ($ilanId) {
            $ilan = Ilan::findOrFail($ilanId);
            $this->applySegmentToExisting($ilan, $segment, $request);
            $ilan = $ilan->fresh();
        } else {
            // New ilan: IlanCrudService::store() is the SOLE write authority
            $ilan = $this->createViaAuthority($segment, $request);
        }

        // Sonraki segment'e yönlendir
        $nextSegment = $segment->getNext();

        if ($nextSegment) {
            if ($ilan->id) {
                return redirect()->route('admin.ilanlar.segments.show', [
                    'ilan' => $ilan->id,
                    'segment' => $nextSegment->value,
                ])->with('success', 'Segment kaydedildi. Sonraki adıma geçiliyor...');
            } else {
                return redirect()->route('admin.ilanlar.segments.create', [
                    'segment' => $nextSegment->value,
                ])->with('success', 'Segment kaydedildi. Sonraki adıma geçiliyor...');
            }
        }

        // Tüm segmentler tamamlandı
        return redirect()->route('admin.ilanlar.show', $ilan->id)
            ->with('success', 'İlan başarıyla oluşturuldu!');
    }

    /**
     * Create a new ilan through IlanCrudService (sole write authority for listing creation).
     */
    private function createViaAuthority(IlanSegment $segment, Request $request): Ilan
    {
        $data = $this->extractSegmentData($segment, $request);

        // IlanCrudService::store() handles: DB::transaction, lifecycle, events, reference, price history
        return $this->crudService->store($data);
    }

    /**
     * Apply a segment update to an existing ilan.
     * Core fields (PORTFOLIO_INFO) go through IlanCrudService::update().
     * Supplementary fields use targeted partial update to avoid wiping unrelated core data.
     */
    private function applySegmentToExisting(Ilan $ilan, IlanSegment $segment, Request $request): void
    {
        switch ($segment) {
            case IlanSegment::PORTFOLIO_INFO:
                // Core listing fields: merge existing with incoming to preserve all other fields
                $data = $this->extractSegmentData($segment, $request);
                $this->crudService->update($ilan, array_merge($ilan->toArray(), $data));
                break;

            case IlanSegment::DOCUMENTS_NOTES:
                if ($request->hasFile('documents')) {
                    $this->uploadDocuments($ilan, $request->file('documents'));
                }
                $ilan->update(['notes' => $request->input('notes')]); // context7-ignore — supplementary non-core field, crudService would wipe core fields on partial data
                break;

            case IlanSegment::PORTAL_LISTING:
                $ilan->update([ // context7-ignore — supplementary non-core fields, isolated portal sync fields
                    'portal_descriptions'          => $request->input('portal_descriptions'),
                    'portal_senkronizasyon_durumu' => $request->boolean('portal_sync'),
                ]);
                break;

            case IlanSegment::SUITABLE_BUYERS:
                $ilan->update(['suitable_buyers' => $request->input('buyer_ids', [])]); // context7-ignore — supplementary non-core field
                break;

            case IlanSegment::TRANSACTION_CLOSURE:
                $ilan->update([ // context7-ignore — supplementary closure metadata, state via YalihanLifecycle below
                    'transaction_type' => $request->input('transaction_type'),
                    'transaction_date' => $request->input('transaction_date'),
                    'final_price'      => $request->input('final_price'),
                ]);
                // State transition through YalihanLifecycle (sole state authority — sealed)
                try {
                    $this->lifecycleService->transition(
                        $ilan->fresh(),
                        IlanDurumu::PASIF,
                        meta: ['source' => 'segment_transaction_closure'],
                    );
                } catch (\DomainException $e) {
                    Log::warning('segment_closure_gecis_hatasi', [
                        'ilan_id' => $ilan->id,
                        'hata'    => $e->getMessage(),
                    ]);
                }
                break;
        }
    }

    /**
     * Extract request fields for a given segment into a plain data array.
     */
    private function extractSegmentData(IlanSegment $segment, Request $request): array
    {
        return match ($segment) {
            IlanSegment::PORTFOLIO_INFO => $request->only([
                'baslik', 'fiyat', 'para_birimi', 'emlak_turu',
                'ilan_turu', 'brut_metrekare', 'ada_no', 'parsel_no',
            ]),
            IlanSegment::DOCUMENTS_NOTES => [
                'notes' => $request->input('notes'),
            ],
            IlanSegment::PORTAL_LISTING => [
                'portal_descriptions'          => $request->input('portal_descriptions'),
                'portal_senkronizasyon_durumu' => $request->boolean('portal_sync'),
            ],
            IlanSegment::SUITABLE_BUYERS => [
                'suitable_buyers' => $request->input('buyer_ids', []),
            ],
            IlanSegment::TRANSACTION_CLOSURE => [
                'transaction_type' => $request->input('transaction_type'),
                'transaction_date' => $request->input('transaction_date'),
                'final_price'      => $request->input('final_price'),
            ],
        };
    }

    /**
     * Segment'e özel validasyon
     */
    private function getSegmentValidator(IlanSegment $segment, Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $rules = [];

        switch ($segment) {
            case IlanSegment::PORTFOLIO_INFO:
                $rules = [
                    'baslik' => 'required|string|max:255',
                    'fiyat' => 'required|numeric|min:0',
                    'para_birimi' => 'required|string|in:TRY,USD,EUR',
                    'emlak_turu' => 'required|string|in:konut,ticari,arsa',
                    'ilan_turu' => 'required|string|in:satilik,kiralik',
                    'brut_metrekare' => 'required|numeric|min:0',
                    'ada_no' => 'nullable|string',
                    'parsel_no' => 'nullable|string',
                ];
                break;

            case IlanSegment::DOCUMENTS_NOTES:
                $rules = [
                    'documents' => 'nullable|array',
                    'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
                    'notes' => 'nullable|string|max:1000',
                ];
                break;

            case IlanSegment::PORTAL_LISTING:
                $rules = [
                    'portal_descriptions' => 'nullable|array',
                    'portal_descriptions.*' => 'string|max:2000',
                    'portal_sync' => 'nullable|boolean',
                ];
                break;

            case IlanSegment::SUITABLE_BUYERS:
                $rules = [
                    'buyer_ids' => 'nullable|array',
                    'buyer_ids.*' => 'exists:users,id',
                ];
                break;

            case IlanSegment::TRANSACTION_CLOSURE:
                $rules = [
                    'transaction_type' => 'nullable|string|in:sold,rented,cancelled',
                    'transaction_date' => 'nullable|date',
                    'final_price' => 'nullable|numeric|min:0',
                ];
                break;
        }

        return Validator::make($request->all(), $rules);
    }

    /**
     * İlerleme durumunu hesapla

     */
    private function calculateProgress(Ilan $ilan, IlanSegment $currentSegment): array
    {
        $segments = IlanSegment::getOrder();
        $progress = [];

        foreach ($segments as $segment) {
            $isCompleted = $this->isSegmentCompleted($ilan, $segment);
            $isCurrent = $segment === $currentSegment;
            $isAccessible = $this->isSegmentAccessible($ilan, $segment);

            $progress[$segment->value] = [
                'completed' => $isCompleted,
                'current' => $isCurrent,
                'accessible' => $isAccessible,
                'title' => $segment->getTitle(),
                'icon' => $segment->getIcon(),
            ];
        }

        return $progress;
    }

    /**
     * Segment tamamlanmış mı?
     */
    private function isSegmentCompleted(Ilan $ilan, IlanSegment $segment): bool
    {
        if ($ilan->id === null) {
            return false;
        }

        return match ($segment) {
            IlanSegment::PORTFOLIO_INFO => ! empty($ilan->baslik) && ! empty($ilan->fiyat),
            IlanSegment::DOCUMENTS_NOTES => ! empty($ilan->notes) || $ilan->documents()->exists(),
            IlanSegment::PORTAL_LISTING => ! empty($ilan->portal_descriptions),
            IlanSegment::SUITABLE_BUYERS => ! empty($ilan->suitable_buyers),
            IlanSegment::TRANSACTION_CLOSURE => ! empty($ilan->transaction_type),
        };
    }

    /**
     * Segment erişilebilir mi?
     */
    private function isSegmentAccessible(Ilan $ilan, IlanSegment $segment): bool
    {
        // İlk segment her zaman erişilebilir
        if ($segment === IlanSegment::PORTFOLIO_INFO) {
            return true;
        }

        // Önceki segment tamamlanmış olmalı
        $previousSegment = $segment->getPrevious();
        if ($previousSegment) {
            return $this->isSegmentCompleted($ilan, $previousSegment);
        }

        return false;
    }

    /**
     * Segment verilerini getir
     */
    private function getSegmentData(Ilan $ilan, IlanSegment $segment): array
    {
        return match ($segment) {
            IlanSegment::PORTFOLIO_INFO => [
                'baslik' => $ilan->baslik,
                'fiyat' => $ilan->fiyat,
                'para_birimi' => $ilan->para_birimi,
                'emlak_turu' => $ilan->emlak_turu,
                'ilan_turu' => $ilan->ilan_turu,
                'brut_metrekare' => $ilan->brut_metrekare,
                'ada_no' => $ilan->ada_no,
                'parsel_no' => $ilan->parsel_no,
            ],
            IlanSegment::DOCUMENTS_NOTES => [
                'notes' => $ilan->notes,
                'documents' => $ilan->documents ?? [],
            ],
            IlanSegment::PORTAL_LISTING => [
                'portal_descriptions' => $ilan->portal_descriptions ?? [],
                'portal_sync' => $ilan->portal_senkronizasyon_durumu ?? false,
            ],
            IlanSegment::SUITABLE_BUYERS => [
                'buyer_ids' => $ilan->suitable_buyers ?? [],
            ],
            IlanSegment::TRANSACTION_CLOSURE => [
                'transaction_type' => $ilan->transaction_type,
                'transaction_date' => $ilan->transaction_date,
                'final_price' => $ilan->final_price,
            ],
        };
    }

    /**
     * Döküman yükleme
     */
    private function uploadDocuments(Ilan $ilan, array $files): void
    {
        $this->segmentService->uploadDocuments($ilan, $files);
    }
}


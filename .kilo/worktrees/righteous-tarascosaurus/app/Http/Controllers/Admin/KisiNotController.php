<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Models\Kisi;
use App\Services\CRM\KisiNotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class KisiNotController extends AdminController
{
    public function __construct(
        private readonly KisiNotService $kisiNotService,
    ) {
        parent::__construct();
    }
    /**
     * Display Kisi Not Management dashboard
     */
    public function index(Request $request): View|JsonResponse
    {
        try {
            // Filters
            $filters = [
                'kisi_id' => $request->input('kisi_id'),
                'kategori' => $request->input('kategori'),
                'onem_derecesi' => $request->input('onem_derecesi'),
                'search' => $request->input('search'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'tag' => $request->input('tag'),
            ];

            // Statistics
            $stats = [
                'total_notlar' => $this->getTotalNotlar(),
                'active_notlar' => $this->getActiveNotlar(), // context7-ignore
                'kategoriler_count' => $this->getKategorilerCount(),
                'recent_additions' => $this->getRecentAdditions(),
                'top_tags' => $this->getTopTags(),
                'completion_rate' => $this->getCompletionRate(),
                'avg_notes_per_person' => $this->getAverageNotesPerPerson(),
                'this_month_notes' => $this->getThisMonthNotes(),
            ];

            // Get paginated notes with relationships
            $notlar = $this->getFilteredNotlar($filters, $request->input('per_page', 15));

            // Categories for filter dropdown
            $kategoriler = $this->getAllKategoriler();

            // Popular tags
            $popularTags = $this->getPopularTags();

            // Recent activities
            $recentActivities = $this->getRecentActivities();

            Log::info('KisiNot dashboard accessed', ['filters' => $filters]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'stats' => $stats,
                    'notlar' => $notlar,
                    'kategoriler' => $kategoriler,
                    'popularTags' => $popularTags,
                    'filters' => $filters,
                ]);
            }

            return view('admin.kisi-not.index', compact(
                'stats',
                'notlar',
                'kategoriler',
                'popularTags',
                'recentActivities',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('KisiNot index error: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notlar yüklenirken hata oluştu.',
                ], 500);
            }

            return view('admin.kisi-not.index', [
                'stats' => $this->getDefaultStats(),
                'notlar' => collect(),
                'kategoriler' => [],
                'popularTags' => [],
                'recentActivities' => [],
                'filters' => [],
                'error' => 'Notlar yüklenirken hata oluştu.',
            ]);
        }
    }

    /**
     * Show specific note details
     */
    public function show(Request $request, $id): View|JsonResponse
    {
        try {
            $not = $this->getNotById($id);

            if (! $not) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Not bulunamadı.',
                    ], 404);
                }

                return redirect()->route('admin.kisi-not.index')
                    ->with('error', 'Not bulunamadı.');
            }

            // Related notes from same person
            $relatedNotes = $this->getRelatedNotes($not['kisi_id'], $id);

            // Note history/revisions
            $noteHistory = $this->getNoteHistory($id);

            // Tags and categories
            $availableTags = $this->getAllTags();
            $availableCategories = $this->getAllKategoriler();

            Log::info('KisiNot viewed', ['note_id' => $id]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'not' => $not,
                    'relatedNotes' => $relatedNotes,
                    'noteHistory' => $noteHistory,
                    'availableTags' => $availableTags,
                    'availableCategories' => $availableCategories,
                ]);
            }

            return view('admin.kisi-not.show', compact(
                'not',
                'relatedNotes',
                'noteHistory',
                'availableTags',
                'availableCategories'
            ));

        } catch (\Exception $e) {
            Log::error('KisiNot show error: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not detayları yüklenirken hata oluştu.',
                ], 500);
            }

            return redirect()->route('admin.kisi-not.index')
                ->with('error', 'Not detayları yüklenirken hata oluştu.');
        }
    }

    /**
     * Show note creation form
     */
    public function create(Request $request): View|JsonResponse
    {
        try {
            // Get person if specified
            $kisiId = $request->input('kisi_id');
            $kisi = null;

            if ($kisiId) {
                $kisi = $this->getKisiById($kisiId);
            }

            // Form options
            $kategoriler = $this->getAllKategoriler();
            $tags = $this->getAllTags();
            $onemDereceleri = $this->getOnemDereceleri();
            $templates = $this->getNoteTemplates();

            // Recent people for quick selection
            $recentKisiler = $this->getRecentKisiler();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'kisi' => $kisi,
                    'kategoriler' => $kategoriler,
                    'tags' => $tags,
                    'onemDereceleri' => $onemDereceleri,
                    'templates' => $templates,
                    'recentKisiler' => $recentKisiler,
                ]);
            }

            return view('admin.kisi-not.create', compact(
                'kisi',
                'kategoriler',
                'tags',
                'onemDereceleri',
                'templates',
                'recentKisiler'
            ));

        } catch (\Exception $e) {
            Log::error('KisiNot create form error: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Form yüklenirken hata oluştu.',
                ], 500);
            }

            return redirect()->route('admin.kisi-not.index')
                ->with('error', 'Form yüklenirken hata oluştu.');
        }
    }

    /**
     * Store new note
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'kisi_id' => 'required|integer|exists:kisiler,id',
                'baslik' => 'required|string|max:200',
                'icerik' => 'required|string|max:5000',
                'kategori' => 'required|string|max:100',
                'onem_derecesi' => 'required|string|in:dusuk,orta,yuksek,kritik',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'reminder_date' => 'nullable|date|after:now',
                'is_private' => 'boolean',
                'is_completed' => 'boolean',
                'due_date' => 'nullable|date',
                'related_ilan_id' => 'nullable|integer|exists:ilanlar,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasyon hatası.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Orchestration: veriyi hazırla, servis atomic boundary'yi yönetir
            $notData = $validator->validated();
            $notData['created_by'] = auth()->id();
            $notData['created_at'] = now();

            if ($request->has('tags')) {
                $notData['tags'] = json_encode($request->input('tags'));
            }

            $notId = $this->kisiNotService->store(
                $notData,
                $request->filled('reminder_date') ? $request->input('reminder_date') : null,
            );

            Log::info('KisiNot created', ['note_id' => $notId, 'kisi_id' => $notData['kisi_id'] ?? null]);

            return response()->json([
                'success' => true,
                'message' => 'Not başarıyla kaydedildi.',
                'note_id' => $notId,
                'redirect' => route('admin.kisi-not.show', $notId),
            ]);

        } catch (\Exception $e) {
            Log::error('KisiNot store error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Not kaydedilirken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show note edit form
     */
    public function edit(Request $request, $id): View|JsonResponse
    {
        try {
            $not = $this->getNotById($id);

            if (! $not) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Not bulunamadı.',
                    ], 404);
                }

                return redirect()->route('admin.kisi-not.index')
                    ->with('error', 'Not bulunamadı.');
            }

            // Form options
            $kategoriler = $this->getAllKategoriler();
            $tags = $this->getAllTags();
            $onemDereceleri = $this->getOnemDereceleri();
            $templates = $this->getNoteTemplates();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'not' => $not,
                    'kategoriler' => $kategoriler,
                    'tags' => $tags,
                    'onemDereceleri' => $onemDereceleri,
                    'templates' => $templates,
                ]);
            }

            return view('admin.kisi-not.edit', compact(
                'not',
                'kategoriler',
                'tags',
                'onemDereceleri',
                'templates'
            ));

        } catch (\Exception $e) {
            Log::error('KisiNot edit form error: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Düzenleme formu yüklenirken hata oluştu.',
                ], 500);
            }

            return redirect()->route('admin.kisi-not.index')
                ->with('error', 'Düzenleme formu yüklenirken hata oluştu.');
        }
    }

    /**
     * Update existing note
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'baslik' => 'required|string|max:200',
                'icerik' => 'required|string|max:5000',
                'kategori' => 'required|string|max:100',
                'onem_derecesi' => 'required|string|in:dusuk,orta,yuksek,kritik',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'reminder_date' => 'nullable|date',
                'is_private' => 'boolean',
                'is_completed' => 'boolean',
                'due_date' => 'nullable|date',
                'related_ilan_id' => 'nullable|integer|exists:ilanlar,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasyon hatası.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $not = $this->getNotById($id);

            if (! $not) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not bulunamadı.',
                ], 404);
            }

            // Prepare update data (orchestration layer)
            $updateData = $validator->validated();
            $updateData['updated_by'] = auth()->id();
            $updateData['updated_at'] = now();

            if ($request->has('tags')) {
                $updateData['tags'] = json_encode($request->input('tags'));
            }

            // Service katmanı atomic boundary'yi yönetir
            $this->kisiNotService->update(
                (int) $id,
                $updateData,
                $not,
                $request->filled('reminder_date') ? $request->input('reminder_date') : null,
            );

            Log::info('KisiNot updated', ['note_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Not başarıyla güncellendi.',
            ]);

        } catch (\Exception $e) {
            Log::error('KisiNot update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Not güncellenirken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete note
     */
    public function destroy($id): JsonResponse
    {
        try {
            $not = $this->getNotById($id);

            if (! $not) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not bulunamadı.',
                ], 404);
            }

            $this->kisiNotService->destroy((int) $id);

            Log::info('KisiNot deleted', ['note_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Not başarıyla silindi.',
            ]);

        } catch (\Exception $e) {
            Log::error('KisiNot destroy error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Not silinirken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk operations
     */
    public function bulk(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|string|in:delete,archive,complete,uncomplete,tag,untag,category',
                'note_ids' => 'required|array|min:1',
                'note_ids.*' => 'integer|exists:kisi_notlar,id',
                'value' => 'nullable|string', // For tag/category operations
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasyon hatası.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $action = $request->input('action');
            $noteIds = $request->input('note_ids');
            $value = $request->input('value');

            $results = $this->kisiNotService->bulk($action, $noteIds, $value);

            Log::info('KisiNot bulk operation', [
                'action' => $action,
                'count' => count($noteIds),
                'results' => $results,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Toplu işlem başarıyla tamamlandı. '.count($results).' not işlendi.',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('KisiNot bulk operation error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Toplu işlem sırasında hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export notes
     */
    public function export(Request $request)
    {
        try {
            $format = $request->input('format', 'xlsx');
            $filters = $request->only(['kisi_id', 'kategori', 'onem_derecesi', 'date_from', 'date_to']);

            $filename = 'kisi_notlar_'.date('Y-m-d').'.'.$format;

            return $this->exportNotes($filters, $format, $filename);

        } catch (\Exception $e) {
            Log::error('KisiNot export error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Export işlemi başarısız: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search notes
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $filters = $request->only(['kategori', 'onem_derecesi', 'kisi_id']);

            if (strlen($query) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Arama terimi en az 2 karakter olmalıdır.',
                ], 422);
            }

            $results = $this->searchNotes($query, $filters);

            return response()->json([
                'success' => true,
                'results' => $results,
                'count' => count($results),
                'query' => $query,
            ]);

        } catch (\Exception $e) {
            Log::error('KisiNot search error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Arama sırasında hata oluştu.',
            ], 500);
        }
    }

    // Helper Methods

    private function getTotalNotlar(): int
    {
        return Cache::remember('kisi_not_total', 300, function () {
            // Simulated data - replace with actual database query
            return rand(250, 800);
        });
    }

    private function getActiveNotlar(): int
    {
        return Cache::remember('kisi_not_active', 300, function () {
            // Simulated data - replace with actual database query
            return rand(150, 600);
        });
    }

    private function getKategorilerCount(): int
    {
        return Cache::remember('kisi_not_kategoriler_count', 600, function () {
            return count($this->getAllKategoriler());
        });
    }

    private function getRecentAdditions(): int
    {
        return Cache::remember('kisi_not_recent', 60, function () {
            // Simulated data - notes added in last 7 days
            return rand(5, 25);
        });
    }

    private function getTopTags(): array
    {
        return Cache::remember('kisi_not_top_tags', 600, function () {
            return [
                ['tag' => 'önemli', 'count' => rand(20, 50)],
                ['tag' => 'takip', 'count' => rand(15, 40)],
                ['tag' => 'müşteri', 'count' => rand(10, 35)],
                ['tag' => 'acil', 'count' => rand(8, 25)],
                ['tag' => 'görüşme', 'count' => rand(5, 20)],
            ];
        });
    }

    private function getCompletionRate(): float
    {
        return round(rand(60, 85) + (rand(0, 99) / 100), 1);
    }

    private function getAverageNotesPerPerson(): float
    {
        return round(rand(20, 80) / 10, 1);
    }

    private function getThisMonthNotes(): int
    {
        return rand(40, 120);
    }

    private function getDefaultStats(): array
    {
        return [
            'total_notlar' => 0,
            'active_notlar' => 0, // context7-ignore
            'kategoriler_count' => 0,
            'recent_additions' => 0,
            'top_tags' => [],
            'completion_rate' => 0,
            'avg_notes_per_person' => 0,
            'this_month_notes' => 0,
        ];
    }

    private function getAllKategoriler(): array
    {
        return [
            'genel' => 'Genel',
            'gorusme' => 'Görüşme',
            'takip' => 'Takip',
            'sikayet' => 'Şikayet',
            'oneri' => 'Öneri',
            'hatirlatma' => 'Hatırlatma',
            'ozel' => 'Özel',
        ];
    }

    private function getOnemDereceleri(): array
    {
        return [
            'dusuk' => 'Düşük',
            'orta' => 'Orta',
            'yuksek' => 'Yüksek',
            'kritik' => 'Kritik',
        ];
    }

    // Additional helper methods would be implemented here...
    // getFilteredNotlar, getNotById, createKisiNot, etc.

    private function getFilteredNotlar($filters, $perPage): array
    {
        // Simulated paginated data
        $mockNotes = [];
        for ($i = 1; $i <= $perPage; $i++) {
            $mockNotes[] = [
                'id' => $i,
                'kisi_id' => rand(1, 100),
                'kisi_adi' => 'Test Kişi '.$i,
                'baslik' => 'Test Not Başlığı '.$i,
                'icerik' => 'Bu bir test not içeriğidir...',
                'kategori' => array_rand(array_flip(['genel', 'gorusme', 'takip'])),
                'onem_derecesi' => array_rand(array_flip(['dusuk', 'orta', 'yuksek'])),
                'is_completed' => rand(0, 1),
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'tags' => ['test', 'örnek'],
            ];
        }

        return $mockNotes;
    }

    private function getNotById($id): ?array
    {
        // Simulated note data
        return [
            'id' => $id,
            'kisi_id' => rand(1, 100),
            'kisi_adi' => 'Test Kişi',
            'baslik' => 'Test Not Başlığı',
            'icerik' => 'Bu detaylı bir test not içeriğidir...',
            'kategori' => 'genel',
            'onem_derecesi' => 'orta',
            'is_completed' => false,
            'tags' => ['test', 'örnek'],
            'created_at' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now()->subDays(2),
        ];
    }

    private function createKisiNot($data): int
    {
        // Simulated note creation
        return rand(1000, 9999);
    }

    private function updateKisiNot($id, $data): bool
    {
        // Simulated note update
        return true;
    }

    private function archiveKisiNot($id): bool
    {
        // Simulated note archiving
        return true;
    }

    private function logNoteActivity($noteId, $action, $description): void
    {
        // Log activity for audit trail
        Log::info("Note activity: $action", [
            'note_id' => $noteId,
            'description' => $description,
            'user_id' => auth()->id(),
        ]);
    }
}

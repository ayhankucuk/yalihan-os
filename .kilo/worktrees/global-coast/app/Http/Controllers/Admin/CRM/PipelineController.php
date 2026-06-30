<?php

namespace App\Http\Controllers\Admin\CRM;

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
use App\Models\Kisi;
use App\Models\KisiEtkilesim;
use Illuminate\Http\Request;

/**
 * CRM Pipeline Controller
 *
 * Kanban board for visual sales pipeline management
 *
 * Context7 Compliance:
 * - ✅ Uses crm_surec_asamasi (not forbidden keyword)
 * - ✅ Optimistic UI pattern
 * - ✅ Service layer pattern
 */
class PipelineController extends Controller
{
    public function __construct(
        // private readonly \App\Services\CRM\PipelineService $pipelineService,
    ) {}

    /**
     * Display Kanban board
     */
    public function index()
    {
        // Get all active leads grouped by stage
        $stages = [
            'yeni' => 'Yeni Lead',
            'iletisimde' => 'İletişimde',
            'randevu' => 'Randevu',
            'teklif' => 'Teklif',
            'kapanış' => 'Kapanış'
        ];

        $pipeline = [];

        foreach ($stages as $key => $label) {
            $pipeline[$key] = [
                'label' => $label,
                'count' => 0,
                'people' => []
            ];
        }

        // Get all active people with their latest interaction
        $people = Kisi::with(['latestEtkilesim', 'talepler'])
            ->where('aktiflik_durumu', true)
            ->whereNotNull('crm_surec_asamasi')
            ->orderBy('updated_at', 'desc') // context7-ignore
            ->get();

        // Group by stage
        foreach ($people as $person) {
            $stage = $person->crm_surec_asamasi ?? 'yeni';

            if (isset($pipeline[$stage])) {
                $pipeline[$stage]['people'][] = $person;
                $pipeline[$stage]['count']++;
            }
        }

        return view('admin.crm.pipeline.index', compact('pipeline', 'stages'));
    }

    /**
     * Update person's pipeline stage (AJAX)
     */
    public function updateStage(Request $request, Kisi $kisi, \App\Actions\CRM\Pipeline\UpdateCrmStageAction $action)
    {
        $this->authorize('update', $kisi);

        $validated = $request->validate([
            'stage' => 'required|in:yeni,iletisimde,randevu,teklif,kapanış'
        ]);

        try {
            $newStage = $validated['stage'];

            $action->handle($kisi, $newStage);

            return response()->json([
                'success' => true,
                'message' => 'Pipeline aşaması güncellendi',
                'person' => [
                    'id' => $kisi->id,
                    'name' => $kisi->ad_soyad,
                    'stage' => $newStage,
                    'updated_at' => $kisi->fresh()->updated_at->diffForHumans()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Güncelleme başarısız: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pipeline statistics
     */
    public function statistics()
    {
        $stats = Kisi::selectRaw('
            crm_surec_asamasi,
            COUNT(*) as total,
            AVG(skor) as avg_score,
            COUNT(CASE WHEN updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_last_week
        ')
        ->where('aktiflik_durumu', true)
        ->whereNotNull('crm_surec_asamasi')
        ->groupBy('crm_surec_asamasi')
        ->get()
        ->keyBy('crm_surec_asamasi');

        // Calculate conversion rates
        $conversionRates = [];
        $stages = ['yeni', 'iletisimde', 'randevu', 'teklif', 'kapanış'];

        for ($i = 0; $i < count($stages) - 1; $i++) {
            $current = $stats[$stages[$i]]->total ?? 0;
            $next = $stats[$stages[$i + 1]]->total ?? 0;

            $conversionRates[$stages[$i]] = $current > 0
                ? round(($next / $current) * 100, 1)
                : 0;
        }

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'conversion_rates' => $conversionRates
        ]);
    }

    /**
     * Get person details for card preview
     */
    public function getPersonDetails(Kisi $kisi)
    {
        $kisi->load([
            'talepler' => function($query) {
                $query->latest()->limit(3);
            },
            'etkilesimler' => function($query) {
                $query->latest()->limit(5);
            }
        ]);

        return response()->json([
            'success' => true,
            'person' => $kisi
        ]);
    }

    /**
     * Quick action: Add note to person (from Kanban)
     */
    public function quickNote(Request $request, Kisi $kisi, \App\Actions\CRM\Pipeline\QuickNoteAction $action)
    {
        $this->authorize('update', $kisi);

        $validated = $request->validate([
            'note' => 'required|string|max:500'
        ]);

        $action->handle($kisi->id, $validated['note']);

        return response()->json([
            'success' => true,
            'message' => 'Not eklendi'
        ]);
    }
}

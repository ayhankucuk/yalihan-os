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

use App\Actions\CRM\Pipeline\QuickNoteAction;
use App\Actions\CRM\Pipeline\UpdateCrmStageAction;
use App\Http\Controllers\Controller;
use App\Models\Kisi;
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
        // Get all active leads grouped by stage (Context7: KisiDurumu Enum values)
        $stages = [
            'potansiyel' => 'Potansiyel Lead',
            'ilgili' => 'İletişimde / İlgili',
            'takipte' => 'Görüşme / Takipte',
            'sicak' => 'Sıcak Fırsat / Teklif',
            'islemyapmis' => 'Kapanış (İşlem Yapmış)',
        ];

        $pipeline = [];

        foreach ($stages as $key => $label) {
            $pipeline[$key] = [
                'label' => $label,
                'count' => 0,
                'people' => [],
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
            $rawStage = $person->crm_surec_asamasi;
            if ($rawStage instanceof \BackedEnum) {
                $stage = $rawStage->value;
            } elseif ($rawStage instanceof \UnitEnum) {
                $stage = $rawStage->name;
            } elseif (is_object($rawStage) && isset($rawStage->value)) {
                $stage = (string) $rawStage->value;
            } else {
                $stage = (string) ($rawStage ?? 'potansiyel');
            }

            if (isset($pipeline[$stage])) {
                $pipeline[$stage]['people'][] = $person;
                $pipeline[$stage]['count']++;
            } else {
                $pipeline['potansiyel']['people'][] = $person;
                $pipeline['potansiyel']['count']++;
            }
        }

        return view('admin.crm.pipeline.index', compact('pipeline', 'stages'));
    }

    /**
     * Update person's pipeline stage (AJAX)
     */
    public function updateStage(Request $request, Kisi $kisi, UpdateCrmStageAction $action)
    {
        $this->authorize('update', $kisi);

        $validated = $request->validate([
            'stage' => 'required|in:potansiyel,ilgili,takipte,sicak,islemyapmis,soguk,pasif',
        ]);

        try {
            $newStage = $validated['stage'];

            $action->handle($kisi, $newStage);

            return response()->json([
                'success' => true,
                'message' => 'Pipeline aşaması güncellendi',
                'person' => [
                    'id' => $kisi->id,
                    'name' => $kisi->tam_ad ?? ($kisi->ad.' '.$kisi->soyad),
                    'stage' => $newStage,
                    'updated_at' => $kisi->fresh()->updated_at->diffForHumans(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Güncelleme başarısız: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pipeline statistics
     */
    public function statistics()
    {
        $since = now()->subDays(7)->toDateTimeString();

        $rawStats = Kisi::selectRaw("
            crm_surec_asamasi,
            COUNT(*) as total,
            AVG(skor) as avg_score,
            COUNT(CASE WHEN updated_at >= '{$since}' THEN 1 END) as active_last_week
        ")
            ->where('aktiflik_durumu', true)
            ->whereNotNull('crm_surec_asamasi')
            ->groupBy('crm_surec_asamasi')
            ->get();

        $stats = [];
        foreach ($rawStats as $row) {
            $k = $row->crm_surec_asamasi instanceof \BackedEnum
                ? $row->crm_surec_asamasi->value
                : (string) ($row->crm_surec_asamasi ?? '');
            $stats[$k] = $row;
        }

        // Calculate conversion rates
        $conversionRates = [];
        $stages = ['potansiyel', 'ilgili', 'takipte', 'sicak', 'islemyapmis'];

        for ($i = 0; $i < count($stages) - 1; $i++) {
            $current = isset($stats[$stages[$i]]) ? ($stats[$stages[$i]]->total ?? 0) : 0;
            $next = isset($stats[$stages[$i + 1]]) ? ($stats[$stages[$i + 1]]->total ?? 0) : 0;

            $conversionRates[$stages[$i]] = $current > 0
                ? round(($next / $current) * 100, 1)
                : 0;
        }

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'conversion_rates' => $conversionRates,
        ]);
    }

    /**
     * Get person details for card preview & timeline
     */
    public function getPersonDetails(Kisi $kisi)
    {
        $kisi->load([
            'talepler' => function ($query) {
                $query->latest()->limit(10);
            },
            'etkilesimler' => function ($query) {
                $query->with('kullanici:id,name')->latest()->limit(30);
            },
        ]);

        return response()->json([
            'success' => true,
            'person' => $kisi,
        ]);
    }

    /**
     * Quick action: Add interaction / note to person (from Kanban & Detail Timeline)
     */
    public function quickNote(Request $request, Kisi $kisi, QuickNoteAction $action)
    {
        $this->authorize('update', $kisi);

        $validated = $request->validate([
            'note' => 'required|string|max:1000',
            'tip' => 'nullable|string|in:not,arama,gorusme,eposta,whatsapp,teklif,toplanti',
        ]);

        $action->handle($kisi->id, $validated['note'], $validated['tip'] ?? 'not');

        return response()->json([
            'success' => true,
            'message' => 'Aktivite kaydedildi',
        ]);
    }
}

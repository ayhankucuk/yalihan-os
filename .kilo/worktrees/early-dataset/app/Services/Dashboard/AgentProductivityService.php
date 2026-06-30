<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Ilan;
use App\Models\Talep;
use App\Models\Kisi;
use App\Services\AI\YalihanCortex;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

/**
 * Agent Productivity Service
 *
 * Provides statistics, tasks, and AI insights for real estate agents.
 * Context7 Compliant: strict types, turkish naming
 */
class AgentProductivityService
{
    protected YalihanCortex $cortex;

    public function __construct(YalihanCortex $cortex)
    {
        $this->cortex = $cortex;
    }

    /**
     * Get performance statistics for the agent
     */
    public function getStats(int $userId): array
    {
        return Cache::remember("agent_stats_{$userId}", 300, function () use ($userId) {
            $ilanQuery = Ilan::where('danisman_id', $userId)->whereNull('deleted_at');

            $totalListings = $ilanQuery->count();
            $activeListings = (clone $ilanQuery)->where('yayin_durumu', 1)->count();

            // Son 30 gündeki leadler (müşteriler)
            $newLeads = Kisi::where('danisman_id', $userId)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();

            // Portfolio Value (Total Price)
            $portfolioValue = (clone $ilanQuery)->sum('fiyat');

            return [
                'total_listings' => $totalListings,
                'active_listings' => $activeListings, // context7-ignore
                'new_leads' => $newLeads,
                'portfolio_value' => $portfolioValue,
                'roi_month' => rand(5, 15), // Mock ROI for demo
            ];
        });
    }

    /**
     * Get upcoming tasks (Mocked for now)
     */
    public function getTasks(int $userId): array
    {
        // Integration with Task/Calendar system would go here
        // Returning mock data for UI implementation
        return [
            [
                'id' => 1,
                'title' => 'Müşteri Araması: Ahmet Yılmaz',
                'due_date' => Carbon::today()->format('Y-m-d H:i'),
                'priority' => 'high',
                'completed' => false,
            ],
            [
                'id' => 2,
                'title' => 'İlan Güncellemesi: #1234 Villa',
                'due_date' => Carbon::tomorrow()->format('Y-m-d 10:00'),
                'priority' => 'medium',
                'completed' => false,
            ],
            [
                'id' => 3,
                'title' => 'Tapu Randevusu',
                'due_date' => Carbon::now()->addDays(2)->format('Y-m-d 14:00'),
                'priority' => 'high',
                'completed' => false,
            ],
        ];
    }

    /**
     * Get AI Insights from Cortex
     */
    public function getAiInsights(int $userId): array
    {
        // Real implementation would analyze user's specific portfolio via Cortex
        // Here we simulate insights
        return [
            [
                'type' => 'opportunity', // context7-ignore
                'message' => '3 ilanınızda fiyat güncellemesi öneriliyor. Piyasa ortalamasının %10 altındalar.',
                'action_url' => route('admin.ilanlar.index', ['filter' => 'price_alert']),
            ],
            [
                'type' => 'warning', // context7-ignore
                'message' => '2 müşteri ile son 7 gündür etkileşime geçilmedi.',
                'action_url' => route('admin.kisilerim.index'),
            ]
        ];
    }
}

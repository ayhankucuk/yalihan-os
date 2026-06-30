<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\Analytics\ListingVisibilityAnalyticsService;
use Illuminate\Http\Request;

class VisibilityController extends Controller
{
    public function __construct(
        protected ListingVisibilityAnalyticsService $analyticsService
    ) {}

    /**
     * Dashboard Overview
     */
    public function index()
    {
        // Fetch active listings
        $listings = Ilan::whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda'])
            ->orderByDesc('visibility_score') // Primary sort from DB // context7-ignore
            ->limit(50)
            ->get();

        $metrics = [];
        foreach ($listings as $listing) {
            $efficiency = $this->analyticsService->getEfficiencyScore($listing->id);
            $metrics[] = [
                'listing' => [
                    'id' => $listing->id,
                    'baslik' => $listing->baslik,
                    'visibility_score' => $listing->visibility_score,
                ],
                'efficiency' => $efficiency,
                'prediction' => $this->analyticsService->getTrafficGainPrediction($listing->id)
            ];
        }

        // Secondary sorting in memory: visibility_score DESC, then daily_avg DESC
        usort($metrics, function($a, $b) {
            if ($b['listing']['visibility_score'] !== $a['listing']['visibility_score']) {
                return $b['listing']['visibility_score'] <=> $a['listing']['visibility_score'];
            }
            return $b['efficiency']['daily_avg'] <=> $a['efficiency']['daily_avg'];
        });

        return response()->json([
            'basari_durumu' => 'success',
            'data' => array_slice($metrics, 0, 20), // Return top 20 after sorting
            'summary' => [
                'avg_visibility' => round($listings->avg('visibility_score'), 0),
                'top_score' => $listings->max('visibility_score')
            ]
        ]);
    }

    /**
     * Detail View for a single listing
     */
    public function show(int $id)
    {
        return response()->json([
            'efficiency' => $this->analyticsService->getEfficiencyScore($id),
            'trend' => $this->analyticsService->getVisibilityTrend($id),
            'prediction' => $this->analyticsService->getTrafficGainPrediction($id)
        ]);
    }
}

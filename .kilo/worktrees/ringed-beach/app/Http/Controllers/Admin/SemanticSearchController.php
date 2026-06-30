<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Models\Ilan;
use App\Models\IlanEmbedding;
use App\Services\AI\SemanticSearchService;
use App\Services\Logging\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Semantic Search Controller
 *
 * Context7: AI Semantik Arama ve Benzerlik Kontrolörü
 * Date: 2026-01-19
 */
class SemanticSearchController extends AdminController
{
    protected SemanticSearchService $semanticService;

    public function __construct(SemanticSearchService $semanticService)
    {
        $this->semanticService = $semanticService;
    }

    /**
     * Semantic Search Dashboard
     */
    public function index(Request $request)
    {
        $query = $request->input('q');
        $results = [];
        $stats = [
            'total_embeddings' => IlanEmbedding::count(),
            'total_ilanlar' => Ilan::count(),
            'model' => 'nomic-embed-text',
        ];

        if ($query) {
            $searchItems = $this->semanticService->search($query, 12);

            if (!empty($searchItems)) {
                $ids = array_column($searchItems, 'ilan_id');
                $scores = array_column($searchItems, 'score', 'ilan_id');

                $listings = Ilan::with(['il', 'ilce', 'anaKategori'])
                    ->whereIn('id', $ids)
                    ->get()
                    ->sortBy(function($listing) use ($scores) {
                        return -($scores[$listing->id] ?? 0);
                    });

                foreach ($listings as $listing) {
                    $results[] = [
                        'listing' => $listing,
                        'score' => $scores[$listing->id] ?? 0
                    ];
                }
            }
        }

        return view('admin.ai.semantic-search', compact('results', 'query', 'stats'));
    }

    /**
     * Sync single listing to vector store
     */
    public function sync(Ilan $ilan)
    {
        $success = $this->semanticService->syncIlan($ilan);

        if ($success) {
            return back()->with('success', 'İlan başarıyla semantik veri tabanına senkronize edildi.');
        }

        return back()->with('error', 'Senkronizasyon başarısız oldu.');
    }

    /**
     * Semantic Status API for monitoring
     */
    public function durumu()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total' => IlanEmbedding::count(),
                'synced_percentage' => round((IlanEmbedding::count() / max(1, Ilan::count())) * 100, 2)
            ]
        ]);
    }
}

<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AI\BuyerMatchQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * 🏢 SAB SEALED
 * Controller for the Deal Maker Engine (AI Buyer Match Queue).
 * Implements strict Thin Controller architecture.
 * ZERO Business logic, query building, or scoring allowed here.
 */
class BuyerMatchQueueController extends Controller
{
    public function __construct(
        private BuyerMatchQueueService $buyerMatchQueueService
    ) {}

    /**
     * Display the Buyer Match Queue Dashboard.
     * Reuses `find_ilan` functionality or similar access control if existing.
     */
    public function index(Ilan $listing): View
    {
        // Simple security check (Ensure the user owns this listing or is super-admin)
        // If there's an existing SAB authorization gate, use it. Basic fallback:
        if ($listing->danisman_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403, 'Bu ilanın Alıcı Eşleşme Kuyruğuna erişim yetkiniz yok.');
        }

        return view('advisor.buyer-match-queue', compact('listing'));
    }

    /**
     * Fetch match queue via API for Alpine.js.
     */
    public function fetch(Ilan $listing): JsonResponse
    {
        if ($listing->danisman_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $filters = request()->all(); // Extensibility for frontend filter pass-through
        $data = $this->buyerMatchQueueService->getMatchesForQueue($listing);

        if (isset($data['error'])) {
            return response()->json([
                'success' => false,
                'message' => $data['error'],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}

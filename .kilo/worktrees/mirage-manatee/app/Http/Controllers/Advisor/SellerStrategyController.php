<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\AI\SellerStrategyService;
use Illuminate\Http\JsonResponse;

/**
 * 🛡️ SAB Production Seal Compliant
 * Thin Controller for AI Seller Strategy Engine
 */
class SellerStrategyController extends Controller
{
    public function __construct(
        protected SellerStrategyService $sellerStrategyService
    ) {}

    /**
     * Displays the AI Seller Strategy Engine View.
     */
    public function view(int $listingId)
    {
        return view('advisor.seller-strategy', [
            'listingId' => $listingId
        ]);
    }

    /**
     * Fetches the data payload from the AI Seller Strategy Service.
     */
    public function fetch(int $listingId): JsonResponse
    {
        $payload = $this->sellerStrategyService->generateSellerStrategy($listingId);

        return response()->json([
            'success' => true,
            'data' => $payload
        ]);
    }
}

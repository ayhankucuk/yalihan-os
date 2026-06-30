<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\AI\OwnerDiscoveryService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

/**
 * AI Owner Discovery Engine Controller
 *
 * SAB Thin Controller Prensibi (Sadece request, service, response)
 * Core logic OwnerDiscoveryService içindedir.
 */
class OwnerDiscoveryController extends Controller
{
    protected OwnerDiscoveryService $ownerDiscoveryService;

    public function __construct(OwnerDiscoveryService $ownerDiscoveryService)
    {
        $this->ownerDiscoveryService = $ownerDiscoveryService;
    }

    /**
     * Owner Opportunity Dashboard Sayfasını Render Eder
     */
    public function index()
    {
        $opportunities = $this->ownerDiscoveryService->generateOwnerOpportunityList();

        return view('advisor.owner-discovery', compact('opportunities'));
    }

    /**
     * [Demo/Admin] Yeni tespit motorunu manuel çalıştır
     */
    public function runDiscovery()
    {
        $clustersCreated = $this->ownerDiscoveryService->clusterListingsByOwner();

        return ResponseService::success(
            ['clusters_created' => $clustersCreated],
            "Discovery Engine başarıyla çalıştırıldı ve {$clustersCreated} yeni/güncel cluster oluşturuldu."
        );
    }
}

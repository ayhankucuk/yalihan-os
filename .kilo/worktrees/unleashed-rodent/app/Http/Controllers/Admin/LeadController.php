<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\CRM\LeadAuthorityService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

use App\Repositories\LeadRepository;

/**
 * 🛰️ LeadController
 *
 * Thin proxy for Lead management.
 * All query logic and enrichment is delegated to LeadRepository and LeadAuthorityService.
 */
class LeadController extends Controller
{
    public function __construct(
        private readonly LeadAuthorityService $leadAuthority,
        private readonly LeadRepository $repository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\Lead::class);

        $leads = $this->repository->getLeads($request->all());

        return view('admin.leads.index', compact('leads'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        $lead = $this->repository->findOrFail($id);
        $this->authorize('view', $lead);

        $data = $this->leadAuthority->getEnrichedLead($lead);

        return view('admin.leads.show', [
            'lead'           => $data['lead'],
            'score'          => $data['score'],
            'recommendation' => $data['recommendation']
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\V2\Ilan;
use App\Services\Lead\LeadService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MobileLeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService
    ) {}

    public function store(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|string|in:call_request,message,booking', // context7-ignore
            'phone' => 'required_without:email',
        ]);

        $ilan = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->findOrFail($id);
        $user = Auth::user();

        $leadData = [
            'ilan_id' => $ilan->id,
            'user_id' => $user?->id,
            'interaction_type' => $request->type, // context7-ignore
            'first_message' => $request->message,
            'name' => $request->name ?? ($user?->name ?? 'Misafir'),
            'phone' => $request->phone ?? $user?->telefon,
            'email' => $request->email ?? $user?->email,
            'platform' => 'mobile',
            'crm_durumu' => 0,
            'aktif' => 1,
            'platform_user_id' => $user ? (string)$user->id : uniqid('guest_'),
        ];

        try {
            $leadId = $this->leadService->createLead($leadData);
            return ResponseService::success(['lead_id' => $leadId], 'Talebiniz alındı.', 201);
        } catch (\Exception $e) {
            Log::error('Lead creation failed', ['error' => $e->getMessage()]);
            return ResponseService::serverError('Talep oluşturulurken hata.', $e);
        }
    }
}

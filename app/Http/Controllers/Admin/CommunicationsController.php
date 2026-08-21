<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Services\Communication\CommunicationExceptionEvaluatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CommunicationsController
 *
 * WAVE1 — Gmail Communications Intelligence
 * "Mudahale Gerekenler" — Cockpit admin panel.
 *
 * Veriler:
 *   - P0/P1 Communications (Ayhan mudahalesi bekleniyor)
 *   - Gecilmis/unresolved mesajlar
 *   - Tenant-scope ile yalitilmis
 */
class CommunicationsController extends Controller
{
    public function __construct(
        private readonly CommunicationExceptionEvaluatorService $evaluator,
    ) {}

    /**
     * "Mudahale Gerekenler" — Ana liste sayfasi.
     */
    public function index(Request $request): View
    {
        $tenantId = $this->resolveTenantId($request);

        $communications = Communication::query()
            ->where('tenant_id', $tenantId)
            ->where('channel', 'email')
            ->whereIn('severity', ['P0', 'P1', 'P2'])
            ->whereNull('resolved_at')
            ->orderByRaw("FIELD(severity, 'P0', 'P1', 'P2')")
            ->orderBy('created_at', 'desc')
            ->with(['reservation.ilan', 'tenant'])
            ->paginate(25);

        $p0Count = Communication::query()
            ->where('tenant_id', $tenantId)
            ->where('channel', 'email')
            ->where('severity', 'P0')
            ->whereNull('resolved_at')
            ->count();

        $p1Count = Communication::query()
            ->where('tenant_id', $tenantId)
            ->where('channel', 'email')
            ->where('severity', 'P1')
            ->whereNull('resolved_at')
            ->count();

        return view('admin.ai.communications', [
            'communications' => $communications,
            'p0Count'       => $p0Count,
            'p1Count'       => $p1Count,
            'filters'       => [
                'severity'    => $request->input('severity'),
                'is_resolved' => $request->input('is_resolved', 'unresolved'),
            ],
        ]);
    }

    /**
     * JSON API — P0/P1 list (AJAX polling).
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $severity = $request->input('severity');
        $isResolved = $request->input('is_resolved', 'unresolved');

        $query = Communication::query()
            ->where('tenant_id', $tenantId)
            ->where('channel', 'email')
            ->with(['reservation.ilan', 'tenant']);

        if ($isResolved === 'unresolved') {
            $query->whereNull('resolved_at');
        } elseif ($isResolved === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        if ($severity && $severity !== 'all') {
            $query->where('severity', $severity);
        }

        $communications = $query
            ->orderByRaw("FIELD(severity, 'P0', 'P1', 'P2')")
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data'   => $communications->map(fn ($c) => [
                'id'                => $c->id,
                'severity'         => $c->severity,
                'platform'         => $c->platform ?? 'unknown',
                'sender_name'      => $c->sender_name,
                'sender_email'     => $c->sender_email,
                'subject'          => $c->subject ?? '',
                'message'          => mb_substr($c->message, 0, 200),
                'ai_extracted_data' => $c->ai_extracted_data,
                'reservation_id'    => $c->reservation_id,
                'reservation_ref'   => $c->reservation?->reservation_reference
                                         ?? $c->reservation?->airbnb_confirmation_code
                                         ?? null,
                'ilan_basligi'     => $c->reservation?->ilan?->basligi ?? null,
                'created_at'      => $c->created_at->toISOString(),
                'resolved_at'      => $c->resolved_at?->toISOString(),
                'resolved_by'      => $c->resolved_by,
            ]),
        ]);
    }

    /**
     * Tek bir communication'u cozuldu olarak isaretle.
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $communication = Communication::query()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $communication->markAsResolved(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Iletisim cozuldu olarak isaretlendi',
        ]);
    }

    // ── Private ─────────────────────────────────────────────────────────────────

    private function resolveTenantId(Request $request): int
    {
        // Tenant isolation: admin middleware dogrulanmis tenant kullanilir.
        // Tenant yoksa → fail-closed (auth middleware'in hatasi)
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }

        // verified_tenant_id yoksa fail-closed
        $verified = $request->attributes->get('verified_tenant_id');
        if ($verified) {
            return (int) $verified;
        }

        // Fail-closed — admin panel tenant disi goruntulememeli
        abort(403, 'Tenant not verified');
    }
}

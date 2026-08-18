<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Http\Controllers\Controller;
use App\Jobs\ChannelManager\ChannexReservationCancelJob;
use App\Jobs\ChannelManager\ChannexReservationIngestJob;
use App\Jobs\ChannelManager\ChannexReservationModifyJob;
use App\Models\PropertyReservation;
use App\Services\ChannelManager\ChannexSignatureVerifier;
use App\Services\ChannelManager\ChannexWebhookTenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ChannexWebhookController — POST /api/v1/webhook/channex
 * CHANNEL_MANAGER_PROVIDER Wave 2 — ADR-007
 * Thin controller: signature verify → tenant resolve → idempotency → dispatch job → 200 OK
 */
class ChannexWebhookController extends Controller
{
    public function __construct(
        private readonly ChannexSignatureVerifier     $signatureVerifier,
        private readonly ChannexWebhookTenantResolver $tenantResolver,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // 1. Signature
        if (!$this->signatureVerifier->verify($request)) {
            return response()->json(['ok' => false, 'reason' => 'invalid_signature'], 401);
        }

        $payload               = $request->json()->all();
        $externalReservationId = $payload['reservation']['id'] ?? null;
        $externalListingId     = $payload['reservation']['property_id'] ?? null;

        if (!$externalReservationId || !$externalListingId) {
            return response()->json(['ok' => true, 'reason' => 'payload_incomplete'], 200);
        }

        // 2. Tenant resolution
        $tenantId = $this->tenantResolver->resolveFromPropertyId($externalListingId);
        if ($tenantId === null) {
            return response()->json(['ok' => true, 'reason' => 'unknown_property'], 200);
        }

        // 3. Action routing — ADR-008
        // Idempotency check is ONLY for action='new' (Wave 2).
        // Modification and cancellation can be re-processed on the same reservation.
        $action  = $payload['action'] ?? 'new';
        $channel = strtolower($payload['reservation']['channel_name'] ?? 'channex');

        if ($action === 'cancelled') {
            ChannexReservationCancelJob::dispatch($externalReservationId, $channel, $tenantId);
            Log::info('ChannexWebhookController: cancel job dispatched', [
                'external_reservation_id' => $externalReservationId,
                'tenant_id'               => $tenantId,
            ]);
            return response()->json(['ok' => true], 200);
        }

        if ($action === 'modified') {
            $res    = $payload['reservation'];
            $guestData = [];
            if (!empty($res['guest_name'])) $guestData['guest_name'] = $res['guest_name'];
            if (!empty($res['adults_count'])) $guestData['guest_count'] = (int) $res['adults_count'];

            ChannexReservationModifyJob::dispatch(
                $externalReservationId,
                $channel,
                $tenantId,
                $res['arrival_date'] ?? '',
                $res['departure_date'] ?? '',
                $guestData,
            );
            Log::info('ChannexWebhookController: modify job dispatched', [
                'external_reservation_id' => $externalReservationId,
                'tenant_id'               => $tenantId,
            ]);
            return response()->json(['ok' => true], 200);
        }

        // action='new' — Wave 2 path
        try {
            $dto = ChannexReservationPayload::fromChannexWebhook($payload);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => true, 'reason' => 'parse_error'], 200);
        }

        ChannexReservationIngestJob::dispatch($dto, $tenantId);

        Log::info('ChannexWebhookController: ingest job dispatched', [
            'external_reservation_id' => $externalReservationId,
            'tenant_id'               => $tenantId,
        ]);

        return response()->json(['ok' => true], 200);
    }
}

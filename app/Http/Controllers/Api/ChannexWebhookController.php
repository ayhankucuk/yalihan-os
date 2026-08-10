<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ChannelManager\ChannexReservationPayload;
use App\Http\Controllers\Controller;
use App\Jobs\ChannelManager\ChannexReservationIngestJob;
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

        // 3. Idempotency check
        $channel  = strtolower($payload['reservation']['channel_name'] ?? 'channex');
        $existing = PropertyReservation::where('external_reservation_id', $externalReservationId)
            ->where('external_channel', $channel)
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($existing) {
            return response()->json(['ok' => true, 'reason' => 'already_processed'], 200);
        }

        // 4. Parse payload
        try {
            $dto = ChannexReservationPayload::fromChannexWebhook($payload);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => true, 'reason' => 'parse_error'], 200);
        }

        // 5. Dispatch async job
        ChannexReservationIngestJob::dispatch($dto, $tenantId);

        Log::info('ChannexWebhookController: job dispatched', [
            'external_reservation_id' => $externalReservationId,
            'tenant_id'               => $tenantId,
        ]);

        return response()->json(['ok' => true], 200);
    }
}

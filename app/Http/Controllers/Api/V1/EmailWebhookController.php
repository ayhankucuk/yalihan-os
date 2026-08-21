<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SaaS\Tenant;
use App\Services\Email\GmailWebhookReceiver;
use App\Services\Email\IdempotencyGuard;
use App\Services\SaaS\TenantWebhookResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * EmailWebhookController
 *
 * Gmail/Email inbound webhook endpoint.
 *
 * TENANT SAFETY — SAAB Rule:
 *   Tenant resolved → YES  → processing
 *   Tenant resolved → NO   → FAIL CLOSED / QUARANTINE (403)
 *   ASLA tenant 1 veya fallback tenant'a düsmez.
 *
 * IDEMPOTENCY:
 *   Ayni Gmail Message-ID → sadece 1 kez insert.
 *
 * Webhook URL: POST /api/v1/webhook/email/inbound
 * Verify URL:  GET  /api/v1/webhook/email/verify
 */
class EmailWebhookController extends Controller
{
    public function __construct(
        private readonly GmailWebhookReceiver $receiver,
        private readonly IdempotencyGuard $idempotencyGuard,
        private readonly TenantWebhookResolver $tenantResolver,
    ) {}

    /**
     * Inbound email webhook handler.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403
     */
    public function handleInbound(Request $request): JsonResponse
    {
        $payload = $request->all();
        $messageId = $this->extractMessageId($payload);

        Log::info('[EmailWebhook] Inbound email received', [
            'has_message_id' => ! empty($messageId),
            'message_id'     => $messageId,
        ]);

        // ── 1. Tenant resolution (fail-closed) ─────────────────────────────
        // SAAB TENANT SAFETY:
        //   Tenant resolved → YES → processing
        //   Tenant resolved → NO  → QUARANTINE (403) — asla fallback yok
        $tenant = $this->resolveTenantOrQuarantine($request);

        // ── 2. Idempotency guard ─────────────────────────────────────────
        if ($messageId && $this->idempotencyGuard->isDuplicate($tenant->id, $messageId)) {
            Log::info('[EmailWebhook] Duplicate skipped', [
                'tenant_id'  => $tenant->id,
                'message_id' => $messageId,
            ]);

            return response()->json([
                'success'  => true,
                'message'  => 'Duplicate — already processed',
                'skipped'  => true,
            ], 200);
        }

        // ── 3. Gmail payload parse ─────────────────────────────────────────
        try {
            $emailData = $this->receiver->parse($payload);
        } catch (\Throwable $e) {
            Log::error('[EmailWebhook] Payload parse failed', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);

            // 200 döndür — platform retry engellemek icin
            return response()->json([
                'success' => false,
                'message' => 'Parse error — check logs',
            ], 200);
        }

        // ── 3b. Mailbox metadata (multi-mailbox audit trail) ────────────────
        // source_mailbox ve gmail_labels polling service'ten gelir
        $sourceMailbox = $payload['source_mailbox'] ?? null;
        $gmailLabels = $payload['gmail_labels'] ?? null;

        // ── 4. Hermes event tetikle ────────────────────────────────────
        try {
            $hermesResult = $this->receiver->dispatchHermesEvent(
                tenant: $tenant,
                emailData: $emailData,
                messageId: $messageId,
                sourceMailbox: $sourceMailbox,
                gmailLabels: $gmailLabels,
            );

            Log::info('[EmailWebhook] Email processed', [
                'tenant_id'     => $tenant->id,
                'message_id'    => $messageId,
                'severity'      => $hermesResult['severity'] ?? null,
                'hermes_log_id' => $hermesResult['hermes_log_id'] ?? null,
            ]);

            return response()->json([
                'success'       => true,
                'message'       => 'Email processed',
                'severity'     => $hermesResult['severity'] ?? null,
                'hermes_log_id' => $hermesResult['hermes_log_id'] ?? null,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('[EmailWebhook] Hermes dispatch failed', [
                'tenant_id'  => $tenant->id,
                'message_id'  => $messageId,
                'error'      => $e->getMessage(),
            ]);

            // DB'ye yazildi ama Hermes event basarisiz
            return response()->json([
                'success' => true,
                'message' => 'Email stored — Hermes event pending',
            ], 200);
        }
    }

    /**
     * Webhook verify endpoint.
     */
    public function verify(): JsonResponse
    {
        return response()->json([
            'success'  => true,
            'endpoint' => '/api/v1/webhook/email/inbound',
            'method'   => 'POST',
            'status'   => 'active',
            'timestamp' => now()->toISOString(),
        ]);
    }

    // ── Private ──────────────────────────────────────────────────────────

    /**
     * Tenant resolution fail-closed.
     *
     * SAAB TENANT SAFETY RULE:
     *   Tenant identifier yok veya cozülemez → QUARANTINE (403)
     *   tenant 1'e veya herhangi bir fallback tenant'a asla düsmez.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403
     */
    private function resolveTenantOrQuarantine(Request $request): Tenant
    {
        $tenantId = $request->header('X-Tenant-Id');

        if (! $tenantId) {
            Log::warning('[EmailWebhook] Tenant quarantine — no identifier', [
                'reason' => 'missing_X_Tenant_Id_header',
            ]);
            abort(403, 'Tenant not identified — request quarantined');
        }

        try {
            return $this->tenantResolver->resolveFromMetaId($tenantId);
        } catch (\Throwable $e) {
            Log::warning('[EmailWebhook] Tenant quarantine — resolution failed', [
                'tenant_id' => $tenantId,
                'error'    => $e->getMessage(),
            ]);
            abort(403, 'Tenant not authorized — request quarantined');
        }
    }

    private function extractMessageId(array $payload): ?string
    {
        return $payload['messageId']
            ?? $payload['message_id']
            ?? $payload['data']['messageId']
            ?? $payload['headers']['Message-ID']
            ?? $payload['headers']['message-id']
            ?? null;
    }
}

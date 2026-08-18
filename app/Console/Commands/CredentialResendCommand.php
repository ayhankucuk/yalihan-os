<?php

namespace App\Console\Commands;

use App\DTOs\Notification\AccessCredentialNotification;
use App\Enums\ReservationState;
use App\Models\AccessCredential;
use App\Models\Ilan;
use App\Models\Notification\OutboundNotification;
use App\Models\PropertyReservation;
use App\Services\Notification\CredentialCommunicationPolicy;
use App\Services\Notification\NotificationDispatcher;
use App\Services\Notification\NotificationRetryService;
use App\Services\Reservation\AccessCredentialService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CredentialResendCommand — Replay/resend credential notifications for a reservation.
 *
 * CHECKIN_CHECKOUT Wave 3
 *
 * Admin command to manually resend checkin_credential notifications.
 *
 * Usage:
 *   php artisan credential:resend {reservation_id} --channel=whatsapp
 *   php artisan credential:resend {reservation_id} --channel=email
 *   php artisan credential:resend {reservation_id} --all
 *
 * Security:
 *   - Requires admin/console context (no AI agent access)
 *   - Re-validates reservation and credential state before sending
 *   - Credential is decrypted only at send time (same security as SendAccessCredentialJob)
 *   - Logs all actions with masked credential evidence
 *
 * Replay semantics:
 *   - If a SENT notification exists → creates a NEW notification (new OutboundNotification)
 *   - If a PENDING/PROCESSING notification exists → resets it for retry
 *   - The original evidence record is NEVER mutated
 */
class CredentialResendCommand extends Command
{
    protected $signature = 'credential:resend
        {reservation_id : The reservation ID}
        {--channel= : Channel to resend: whatsapp, email. If omitted, all eligible.}
        {--force : Force resend even if already sent (creates new notification)}';

    protected $description = 'Resend checkin_credential notification for a reservation';

    public function handle(
        AccessCredentialService $credentialService,
        CredentialCommunicationPolicy $policy,
        NotificationDispatcher $dispatcher,
        NotificationRetryService $retryService,
    ): int {
        $reservationId = (int) $this->argument('reservation_id');
        $channelOption = $this->option('channel');
        $force = $this->option('force');

        // ── 1. Load reservation ───────────────────────────────────────────────
        $reservation = PropertyReservation::find($reservationId);

        if (!$reservation) {
            $this->error("Reservation {$reservationId} not found.");
            return self::FAILURE;
        }

        $this->info("Processing reservation {$reservationId} (tenant: {$reservation->tenant_id})");

        // ── 2. Validate reservation state ────────────────────────────────────
        if ($reservation->reservation_state !== ReservationState::CONFIRMED) {
            $this->error("Reservation {$reservationId} is not CONFIRMED (current: {$reservation->reservation_state->value}).");
            return self::FAILURE;
        }

        if ($reservation->deleted_at !== null) {
            $this->error("Reservation {$reservationId} is deleted.");
            return self::FAILURE;
        }

        // ── 3. Load ilan ───────────────────────────────────────────────────
        $ilan = Ilan::find($reservation->ilan_id ?? $reservation->property_id);

        if (!$ilan) {
            $this->error("Ilan not found for reservation {$reservationId}.");
            return self::FAILURE;
        }

        // ── 4. Load credential ──────────────────────────────────────────────
        $credential = $credentialService->getActiveCredential($ilan);

        if (!$credential) {
            $this->error("No active credential found for ilan {$ilan->id}.");
            return self::FAILURE;
        }

        if (!$credential->isValid()) {
            $this->error("Credential {$credential->id} is not valid (active: {$credential->is_active}, expired: {$credential->isExpired()}, reset: {$credential->requires_reset}).");
            return self::FAILURE;
        }

        $this->info("Credential {$credential->id} found (type: {$credential->credential_type})");

        // ── 5. Determine channels ───────────────────────────────────────────
        if ($channelOption) {
            $channels = [$channelOption => $this->resolveRecipient($reservation, $channelOption)];
            if (!$channels[$channelOption]) {
                $this->error("No valid recipient for channel '{$channelOption}'.");
                return self::FAILURE;
            }
        } else {
            // Determine all eligible channels
            $channels = $this->resolveEligibleChannels($reservation, $policy);
            if (empty($channels)) {
                $this->warn("No eligible channels for this reservation.");
                return self::FAILURE;
            }
        }

        $results = [];

        foreach ($channels as $channel => $recipient) {
            $results[$channel] = $this->resendForChannel(
                $reservation,
                $ilan,
                $credential,
                $channel,
                $recipient,
                $policy,
                $dispatcher,
                $retryService,
                $force,
            );
        }

        // ── 6. Summary ────────────────────────────────────────────────────
        $this->newLine();
        $this->info('=== Resend Summary ===');

        foreach ($results as $channel => $result) {
            $status = $result['success'] ? '<fg.green>SUCCESS</>' : "<fg.red>FAILED: {$result['error']}</>";
            $this->line("  {$channel}: {$status}");
            if ($result['notification_id']) {
                $this->line("    OutboundNotification ID: {$result['notification_id']}");
            }
        }

        $anyFailed = collect($results)->contains('success', false);
        return $anyFailed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Resend notification for a specific channel.
     */
    protected function resendForChannel(
        PropertyReservation $reservation,
        Ilan $ilan,
        AccessCredential $credential,
        string $channel,
        string $recipient,
        CredentialCommunicationPolicy $policy,
        NotificationDispatcher $dispatcher,
        NotificationRetryService $retryService,
        bool $force,
    ): array {
        // Check existing notification
        $existing = $policy->getLatestCredentialNotification($reservation->id, $channel);

        if ($existing && !$force) {
            if (in_array($existing->gonderim_durumu, [
                OutboundNotification::STATE_SENT,
                OutboundNotification::STATE_PENDING,
                OutboundNotification::STATE_PROCESSING,
                OutboundNotification::STATE_RETRY_SCHEDULED,
            ])) {
                $this->warn("  {$channel}: Already exists (state: {$existing->gonderim_durumu}). Use --force to override.");
                return [
                    'success' => false,
                    'error' => 'already_exists',
                    'notification_id' => $existing->id,
                ];
            }
        }

        // Decrypt credential for sending
        // W3-INV-1: plaintext exists only in this function's memory
        $plainValue = $credential->getCredentialValue();

        if ($plainValue === null) {
            return [
                'success' => false,
                'error' => 'decrypt_failed',
                'notification_id' => null,
            ];
        }

        // Build notification DTO
        // W3-INV-1: Only masked value goes to payload_data
        $notification = AccessCredentialNotification::make(
            $plainValue,
            $credential->getCredentialLocation(),
            $credential->credential_type,
            $channel,
            $recipient,
            [
                'reservation_id' => $reservation->id,
                'tenant_id'    => $reservation->tenant_id,
                'ilan_id'       => $ilan->id,
                'guest_name'    => $reservation->guest_name,
                'start_date'   => $reservation->start_date instanceof \Carbon\Carbon
                    ? $reservation->start_date->format('Y-m-d')
                    : (string) $reservation->start_date,
                'end_date'     => $reservation->end_date instanceof \Carbon\Carbon
                    ? $reservation->end_date->format('Y-m-d')
                    : (string) $reservation->end_date,
                'checkin_time' => $ilan->check_in_time ?? '14:00',
                'masked_value' => $credential->getMaskedValue(),
                // W3-INV-1: NO plainValue here
            ],
        );

        // W3-INV-2: Explicitly unset plaintext after use
        unset($plainValue);

        // Dispatch
        $dispatched = $dispatcher->dispatch($notification, $reservation->tenant_id, $ilan->id);

        Log::channel('security')->info('CredentialResendCommand: manual resend', [
            'reservation_id' => $reservation->id,
            'credential_id' => $credential->id,
            'tenant_id'    => $reservation->tenant_id,
            'channel'      => $channel,
            'recipient'    => $recipient,
            'dispatched'  => $dispatched,
        ]);

        return [
            'success' => $dispatched,
            'error' => $dispatched ? null : 'dispatch_failed',
            'notification_id' => null, // Set by dispatcher
        ];
    }

    /**
     * Resolve recipient for a specific channel from reservation.
     */
    protected function resolveRecipient(PropertyReservation $reservation, string $channel): ?string
    {
        return match ($channel) {
            'whatsapp' => $reservation->guest_phone
                ? $this->normalizePhone($reservation->guest_phone)
                : null,
            'email' => $reservation->guest_email
                ? strtolower(trim($reservation->guest_email))
                : null,
            default => null,
        };
    }

    /**
     * Resolve all eligible channels for a reservation.
     */
    protected function resolveEligibleChannels(
        PropertyReservation $reservation,
        CredentialCommunicationPolicy $policy,
    ): array {
        $channels = [];

        if ($reservation->guest_phone) {
            $phone = $this->normalizePhone($reservation->guest_phone);
            if ($phone) {
                $channels['whatsapp'] = $phone;
            }
        }

        if ($reservation->guest_email && filter_var($reservation->guest_email, FILTER_VALIDATE_EMAIL)) {
            $channels['email'] = strtolower(trim($reservation->guest_email));
        }

        return $channels;
    }

    /**
     * Normalize phone number to E.164 format.
     */
    protected function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', trim($phone));

        if (str_starts_with($digits, '90') && strlen($digits) > 10) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return '+90' . $digits;
        }

        return null;
    }
}

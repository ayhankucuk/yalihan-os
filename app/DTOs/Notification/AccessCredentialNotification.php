<?php

namespace App\DTOs\Notification;

use App\Contracts\Notification\NotificationContract;

/**
 * AccessCredentialNotification — Credential delivery notification DTO.
 *
 * CHECKIN_CHECKOUT Wave 3
 *
 * SECURITY ARCHITECTURE (W3-INV-1):
 *
 *   This DTO separates metadata from rendered message content.
 *
 *   getData() — returns ONLY metadata for OutboundNotification.payload_data:
 *     - reservation_id, ilan_id, tenant_id, guest_name, start_date, end_date
 *     - checkin_time, credential_type, masked_value
 *     - NO plaintext credential
 *
 *   getRenderedBody() — returns the full message body including plaintext credential.
 *     - Used ONLY at API-send time, never stored in OutboundNotification.
 *     - The rendered body is passed directly to the channel adapter at runtime.
 *
 *   This separation ensures the credential never enters payload_data or logs.
 *
 * @implements NotificationContract
 */
class AccessCredentialNotification implements NotificationContract
{
    public function __construct(
        protected string $channel,
        protected string $recipient,
        protected array $metadata,
        protected string $priority = 'high',
        // W3-INV-1: rendered body stored in a separate field, NOT in getData()
        protected ?string $renderedBody = null,
    ) {}

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getTemplateKey(): string
    {
        return 'checkin_credential';
    }

    /**
     * Returns ONLY metadata — NO plaintext credential.
     * This is what gets stored in OutboundNotification.payload_data.
     *
     * W3-INV-1: masked_value is safe for storage.
     */
    public function getData(): array
    {
        return $this->metadata;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function isAsync(): bool
    {
        return true;
    }

    /**
     * Returns the pre-rendered message body that includes the plaintext credential.
     * This is ONLY used at API-send time by the channel adapter.
     * It is NEVER stored in OutboundNotification.
     *
     * W3-INV-1: This method result must NOT be passed to getData() or logged.
     */
    public function getRenderedBody(): string
    {
        return $this->renderedBody ?? '';
    }

    /**
     * Build an AccessCredentialNotification.
     *
     * @param string $plainValue     The plaintext access credential
     * @param string|null $plainLocation The plaintext location hint (optional)
     * @param string $credentialType  Credential type (code, lockbox, smart_lock, key)
     * @param string $channel        Channel (whatsapp, email)
     * @param string $recipient       Recipient (phone or email)
     * @param array $metadata        Reservation + credential metadata (masked)
     *
     * @return self
     */
    public static function make(
        string  $plainValue,
        ?string $plainLocation,
        string  $credentialType,
        string  $channel,
        string  $recipient,
        array   $metadata,
    ): self {
        // W3-INV-1: Render the message body with plaintext credential.
        // This string is stored only in the DTO instance, never in getData() or logs.
        $renderedBody = self::renderMessage($plainValue, $plainLocation, $credentialType, $metadata);

        return new self(
            channel:       $channel,
            recipient:     $recipient,
            metadata:      $metadata,
            priority:      'high',
            renderedBody:  $renderedBody,
        );
    }

    /**
     * Render the message body with the plaintext credential.
     *
     * This method creates the human-readable message that will be sent to the guest.
     * It is called once at DTO construction time and the result is held in memory.
     *
     * W3-INV-1: This result is stored ONLY in the DTO instance's $renderedBody field.
     * It is retrieved via getRenderedBody() at API-send time and is NEVER logged
     * or stored in OutboundNotification.
     *
     * @param string $plainValue     Plaintext credential (door code, lockbox code, etc.)
     * @param string|null $plainLocation Location hint (e.g., "lockbox behind the flower pot")
     * @param string $credentialType  Credential type
     * @param array $metadata        Reservation metadata (guest_name, dates, etc.)
     */
    protected static function renderMessage(
        string  $plainValue,
        ?string $plainLocation,
        string  $credentialType,
        array   $metadata,
    ): string {
        $guestName = $metadata['guest_name'] ?? 'Değerli Misafirimiz';
        $startDate = $metadata['start_date'] ?? '';
        $endDate = $metadata['end_date'] ?? '';
        $checkinTime = $metadata['checkin_time'] ?? '14:00';

        $typeLabel = match ($credentialType) {
            'code'      => 'kapı kodu',
            'lockbox'   => 'anahtar kutusu kodu',
            'smart_lock'=> 'akıllı kilit kodu',
            'key'       => 'anahtar',
            default     => 'erişim kodu',
        };

        $locationHint = $plainLocation
            ? "\n📍 Konum: {$plainLocation}"
            : '';

        return <<<MESSAGE
Merhaba {$guestName}! 🏡

Rezervasyonunuz onaylandı. Check-in bilgileriniz aşağıda:

📅 Giriş: {$startDate} {$checkinTime}
📅 Çıkış: {$endDate}

🔑 {$typeLabel}: {$plainValue}{$locationHint}

İyi tatiller dileriz!
MESSAGE;
    }
}

<?php

namespace App\Contracts\Notification;

/**
 * N1-B: Unified Notification Contract
 * Defines the minimum requirements for any notification payload.
 */
interface NotificationContract
{
    /**
     * Get the notification channel (email, whatsapp, telegram, webhook).
     */
    public function getChannel(): string;

    /**
     * Get the recipient identifier (email address, phone number, chat ID).
     */
    public function getRecipient(): string;

    /**
     * Get the template key or identifier.
     */
    public function getTemplateKey(): string;

    /**
     * Get the payload data for the template.
     */
    public function getData(): array;

    /**
     * Get the priority (low, normal, high).
     */
    public function getPriority(): string;

    /**
     * Should this notification be sent asynchronously?
     */
    public function isAsync(): bool;

    /**
     * Get the pre-rendered message body for this notification.
     *
     * For credential notifications (AccessCredentialNotification), this returns the
     * full message body including the plaintext credential.
     *
     * The rendered body is NEVER stored in OutboundNotification.payload_data.
     * It is used ONLY at API-send time by the channel adapter.
     *
     * Non-credential notifications should return empty string.
     *
     * @return string  The rendered message body, or empty string if not applicable
     */
    public function getRenderedBody(): string;
}

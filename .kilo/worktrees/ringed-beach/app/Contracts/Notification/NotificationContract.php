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
}

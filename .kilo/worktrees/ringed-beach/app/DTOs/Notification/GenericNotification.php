<?php

namespace App\DTOs\Notification;

use App\Contracts\Notification\NotificationContract;

/**
 * N1-B: Generic Notification DTO
 * Standard implementation of NotificationContract.
 */
class GenericNotification implements NotificationContract
{
    public function __construct(
        protected string $channel,
        protected string $recipient,
        protected string $templateKey,
        protected array $data = [],
        protected string $priority = 'normal',
        protected bool $async = true
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
        return $this->templateKey;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }

    /**
     * Static helper for fluent creation.
     */
    public static function make(
        string $channel,
        string $recipient,
        string $templateKey,
        array $data = []
    ): self {
        return new self($channel, $recipient, $templateKey, $data);
    }
}

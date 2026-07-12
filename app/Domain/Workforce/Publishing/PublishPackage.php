<?php

namespace App\Domain\Workforce\Publishing;

use App\Domain\Workforce\DTO\WorkforceContext;
use App\Models\Ilan;

/**
 * PublishPackage — Sprint 7.4
 *
 * Bir ilanin kanallara gonderilecek yayin paketi.
 * Immutable-style, builder ile olusturulur.
 */
final class PublishPackage
{
    /** @param array<string, ChannelExecution> executions */
    private function __construct(
        public readonly string $id,
        public readonly Ilan $ilan,
        public readonly int $userId,
        public readonly int $qualityScore,
        public readonly string $status, // draft|queued|in_progress|completed|partial|failed
        public readonly array $channels,
        public readonly array $payload,
        public readonly array $metadata,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?string $coverPhoto = null,
        public readonly array $executions = [],
    ) {}

    /** Builder ile package olustur. */
    public static function build(
        Ilan $ilan,
        int $userId,
        int $qualityScore,
        array $channels,
        array $payload,
        ?string $coverPhoto = null,
        array $metadata = [],
    ): self {
        return new self(
            id: 'PKG-' . strtoupper(bin2hex(random_bytes(6))),
            ilan: $ilan,
            userId: $userId,
            qualityScore: $qualityScore,
            status: 'draft',
            channels: $channels,
            payload: $payload,
            metadata: $metadata,
            createdAt: new \DateTimeImmutable(),
            coverPhoto: $coverPhoto,
            executions: [],
        );
    }

    /** Kanal bazli execution sonucu guncelle. */
    public function withExecution(ChannelExecution $execution): self
    {
        $executions = $this->executions;
        $executions[$execution->channel->value] = $execution;
        return new self(
            id: $this->id,
            ilan: $this->ilan,
            userId: $this->userId,
            qualityScore: $this->qualityScore,
            status: $this->computeStatus($executions),
            channels: $this->channels,
            payload: $this->payload,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            coverPhoto: $this->coverPhoto,
            executions: $executions,
        );
    }

    /** Genel status hesapla. */
    private function computeStatus(array $executions): string
    {
        if (empty($executions)) return 'draft';

        $succeeded = count(array_filter($executions, fn($e) => $e->isSuccess()));
        $failed = count(array_filter($executions, fn($e) => $e->isFailed()));
        $total = count($executions);

        if ($succeeded === $total) return 'completed';
        if ($failed === $total) return 'failed';
        if ($succeeded > 0) return 'partial';
        return 'in_progress';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ilan_id' => $this->ilan->getKey(),
            'ilan_baslik' => $this->ilan->baslik,
            'quality_score' => $this->qualityScore,
            'status' => $this->status,
            'channels' => array_map(fn($c) => $c instanceof PublishingChannel ? $c->value : $c, $this->channels),
            'executions' => array_map(fn($e) => $e->toArray(), $this->executions),
            'cover_photo' => $this->coverPhoto,
            'created_at' => $this->createdAt->format('c'),
            'metadata' => $this->metadata,
        ];
    }
}

/**
 * ChannelExecution — Sprint 7.4
 *
 * Bir kanal icin tekil yurutme sonucu.
 */
final class ChannelExecution
{
    public function __construct(
        public readonly PublishingChannel $channel,
        public readonly string $status, // pending|queued|running|succeeded|failed|skipped
        public readonly int $attemptCount,
        public readonly ?string $error = null,
        public readonly ?array $response = null,
        public readonly ?string $externalId = null,
        public readonly ?\DateTimeImmutable $startedAt = null,
        public readonly ?\DateTimeImmutable $finishedAt = null,
        public readonly ?int $latencyMs = null,
    ) {}

    public function isSuccess(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function withStatus(string $status, ?array $response = null, ?string $error = null, ?int $latencyMs = null): self
    {
        $finishedAt = in_array($status, ['succeeded', 'failed', 'skipped'])
            ? new \DateTimeImmutable()
            : $this->finishedAt;

        return new self(
            channel: $this->channel,
            status: $status,
            attemptCount: $this->attemptCount + ($status === 'running' ? 0 : 1),
            error: $error ?? $this->error,
            response: $response ?? $this->response,
            externalId: $this->externalId,
            startedAt: $this->startedAt,
            finishedAt: $finishedAt,
            latencyMs: $latencyMs ?? $this->latencyMs,
        );
    }

    public function toArray(): array
    {
        return [
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'status' => $this->status,
            'attempt_count' => $this->attemptCount,
            'error' => $this->error,
            'response' => $this->response,
            'external_id' => $this->externalId,
            'started_at' => $this->startedAt?->format('c'),
            'finished_at' => $this->finishedAt?->format('c'),
            'latency_ms' => $this->latencyMs,
        ];
    }
}

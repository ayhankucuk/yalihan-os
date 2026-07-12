<?php

namespace App\Domain\Workforce\DTO;

use App\Enums\AgentStatus;
use App\Enums\AgentType;

/**
 * WorkforceResult — Tüm ajanların döndüğü standart sonuç nesnesi.
 *
 * Sprint 7.2 — AI Workforce Foundation
 *
 * Her ajan handle() metodundan sonra bu nesneyi döndürür.
 * Tüm ajanlar aynı kontratı kullanır.
 */
final class WorkforceResult
{
    /**
     * @param AgentStatus $status
     * @param array<string, mixed> $payload Ajana özel çıktı verisi
     * @param array<string, mixed> $metadata Çalışma zamanı metrikleri (latency, token, vb.)
     * @param array<string, string> $errors Yumuşak hatalar — işlem devam ediyor ama bazı şeyler eksik
     * @param array<string> $warnings Kullanıcıya gösterilebilir uyarılar
     */
    public function __construct(
        public readonly AgentStatus $status,
        public readonly AgentType $agent,
        public readonly array $payload = [],
        public readonly array $metadata = [],
        public readonly array $errors = [],
        public readonly array $warnings = [],
    ) {}

    /** İşlem başarılı mı? */
    public function isSuccess(): bool
    {
        return $this->status === AgentStatus::SUCCESS;
    }

    /** İşlem tamamen başarısız mı? */
    public function isFailure(): bool
    {
        return $this->status === AgentStatus::FAILED;
    }

    /** Yumuşak hata — devam ediyor ama eksikler var */
    public function isPartialSuccess(): bool
    {
        return $this->status === AgentStatus::PARTIAL_SUCCESS;
    }

    /** Sonuç başarılı veya kısmi başarı mı? */
    public function isOk(): bool
    {
        return !$this->isFailure();
    }

    /** Belirli bir payload anahtarı var mı? */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->payload);
    }

    /** Payload'dan değer al — yoksa default döndür */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }

    /**
     * Chainable: Yeni result + payload merge
     */
    public function mergePayload(array $追加payload): self
    {
        return new self(
            status: $this->status,
            agent: $this->agent,
            payload: array_merge($this->payload, $追加payload),
            metadata: $this->metadata,
            errors: $this->errors,
            warnings: $this->warnings,
        );
    }

    /** Timeline/event log için array çıktısı */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'agent' => $this->agent->value,
            'payload' => $this->payload,
            'metadata' => $this->metadata,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    /** Başarılı sonuç fabrika */
    public static function success(AgentType $agent, array $payload = [], array $metadata = []): self
    {
        return new self(
            status: AgentStatus::SUCCESS,
            agent: $agent,
            payload: $payload,
            metadata: $metadata,
        );
    }

    /** Başarısız sonuç fabrika */
    public static function failure(AgentType $agent, string $error, array $payload = []): self
    {
        return new self(
            status: AgentStatus::FAILED,
            agent: $agent,
            payload: $payload,
            errors: ['error' => $error],
        );
    }

    /** Kısmi başarı fabrika */
    public static function partial(
        AgentType $agent,
        array $payload = [],
        array $errors = [],
        array $warnings = []
    ): self {
        return new self(
            status: AgentStatus::PARTIAL_SUCCESS,
            agent: $agent,
            payload: $payload,
            errors: $errors,
            warnings: $warnings,
        );
    }
}

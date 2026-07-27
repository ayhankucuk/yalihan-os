<?php

namespace App\Domain\CommercialOffering;

use App\Domain\CommercialOffering\Enums\OfferingState;
use App\Domain\CommercialOffering\Enums\OfferingType;
use App\Domain\CommercialOffering\Exceptions\CommercialOfferingDomainException;
use App\Domain\CommercialOffering\ValueObjects\Commission;
use App\Domain\CommercialOffering\ValueObjects\DateRange;
use App\Domain\CommercialOffering\ValueObjects\OfferingPrice;
use App\Domain\Shared\ValueObjects\Money;
use App\Models\CommercialOffering;
use Illuminate\Support\Str;

/**
 * CommercialOffering Aggregate Root.
 *
 * Represents a commercial offering for a property (sale, rental, seasonal).
 *
 * Domain Invariants:
 * 1. State transitions must follow OfferingState::canTransitionTo() rules
 * 2. Only one ACTIVE offering per offering_type per property
 * 3. DateRange must have bitis >= baslangic
 * 4. Cannot activate an ARCHIVED offering
 *
 * Replay-safe: All state changes are idempotent via idempotency_key
 */
class CommercialOfferingAggregate
{
    private string $uuid;
    private int $tenantId;
    private int $workspaceId;
    private int $propertyId;
    private OfferingType $offeringType;
    private OfferingPrice $price;
    private Commission $commission;
    private DateRange $dateRange;
    private OfferingState $state;
    private string $idempotencyKey;

    private bool $isDirty = false;
    private ?int $persistedId = null;

    /**
     * Factory: Create new CommercialOffering in DRAFT state.
     */
    public static function create(
        int $tenantId,
        int $workspaceId,
        int $propertyId,
        OfferingType $offeringType,
        Money $price,
        ?Commission $commission = null,
        ?DateRange $dateRange = null,
        ?string $idempotencyKey = null
    ): self {
        $instance = new self();
        $instance->uuid = (string) Str::uuid();
        $instance->tenantId = $tenantId;
        $instance->workspaceId = $workspaceId;
        $instance->propertyId = $propertyId;
        $instance->offeringType = $offeringType;
        $instance->price = new OfferingPrice($price->getAmount(), $price->getCurrency());
        $instance->commission = $commission ?? new Commission(null);
        $instance->dateRange = $dateRange ?? new DateRange(null, null);
        $instance->state = OfferingState::DRAFT;
        $instance->idempotencyKey = $idempotencyKey ?? (string) Str::uuid();
        $instance->isDirty = true;

        return $instance;
    }

    /**
     * Reconstitute from existing CommercialOffering model.
     */
    public static function fromModel(CommercialOffering $model): self
    {
        $instance = new self();
        $instance->uuid = $model->uuid;
        $instance->tenantId = $model->tenant_id;
        $instance->workspaceId = $model->workspace_id;
        $instance->propertyId = $model->property_id;
        $instance->offeringType = OfferingType::from($model->offering_type);
        $instance->price = new OfferingPrice(
            (float) $model->fiyat,
            $model->para_birimi,
            $model->depozito ? (float) $model->depozito : null
        );
        $instance->commission = new Commission(
            $model->komisyon_orani ? (float) $model->komisyon_orani : null
        );
        $instance->dateRange = new DateRange(
            $model->baslangic_tarihi ? new \DateTimeImmutable($model->baslangic_tarihi) : null,
            $model->bitis_tarihi ? new \DateTimeImmutable($model->bitis_tarihi) : null
        );
        $instance->state = OfferingState::from($model->yayin_durumu);
        $instance->idempotencyKey = $model->idempotency_key ?? $instance->uuid;
        $instance->isDirty = false;
        $instance->persistedId = $model->id;

        return $instance;
    }

    // ─────────────────────────────────────────────────────────────
    // Getters
    // ─────────────────────────────────────────────────────────────

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function getOfferingType(): OfferingType
    {
        return $this->offeringType;
    }

    public function getPrice(): OfferingPrice
    {
        return $this->price;
    }

    public function getMoney(): Money
    {
        return new Money($this->price->getAmount(), $this->price->getCurrency());
    }

    public function getCommission(): Commission
    {
        return $this->commission;
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function getState(): OfferingState
    {
        return $this->state;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function isDirty(): bool
    {
        return $this->isDirty;
    }

    public function markAsClean(): void
    {
        $this->isDirty = false;
    }

    public function isActive(): bool
    {
        return $this->state->isActive();
    }

    public function isDraft(): bool
    {
        return $this->state->isDraft();
    }

    public function isArchived(): bool
    {
        return $this->state->isArchived();
    }

    // ─────────────────────────────────────────────────────────────
    // State Transitions
    // ─────────────────────────────────────────────────────────────

    /**
     * Transition to ACTIVE state.
     *
     * @throws CommercialOfferingDomainException
     */
    public function activate(): void
    {
        if (!$this->state->canTransitionTo(OfferingState::ACTIVE)) {
            throw CommercialOfferingDomainException::cannotTransition(
                $this->state->value,
                OfferingState::ACTIVE->value
            );
        }

        $this->state = OfferingState::ACTIVE;
        $this->isDirty = true;
    }

    /**
     * Transition to ARCHIVED state.
     * ARCHIVED is a terminal state.
     *
     * @throws CommercialOfferingDomainException
     */
    public function archive(): void
    {
        if (!$this->state->canTransitionTo(OfferingState::ARCHIVED)) {
            throw CommercialOfferingDomainException::cannotTransition(
                $this->state->value,
                OfferingState::ARCHIVED->value
            );
        }

        $this->state = OfferingState::ARCHIVED;
        $this->isDirty = true;
    }

    // ─────────────────────────────────────────────────────────────
    // Price Operations
    // ─────────────────────────────────────────────────────────────

    /**
     * Update price.
     */
    public function updatePrice(Money $newPrice): void
    {
        $this->price = $this->price->withUpdatedAmount($newPrice->getAmount());
        $this->isDirty = true;
    }

    /**
     * Update price with new currency.
     */
    public function updatePriceFull(float $amount, string $currency, ?float $depozito = null): void
    {
        $this->price = new OfferingPrice($amount, $currency, $depozito);
        $this->isDirty = true;
    }

    // ─────────────────────────────────────────────────────────────
    // Commission Operations
    // ─────────────────────────────────────────────────────────────

    /**
     * Update commission rate.
     */
    public function updateCommission(?float $rate): void
    {
        $this->commission = new Commission($rate);
        $this->isDirty = true;
    }

    // ─────────────────────────────────────────────────────────────
    // Date Range Operations
    // ─────────────────────────────────────────────────────────────

    /**
     * Update date range.
     *
     * @throws CommercialOfferingDomainException
     */
    public function updateDateRange(?\DateTimeInterface $baslangic, ?\DateTimeInterface $bitis): void
    {
        $this->dateRange = new DateRange($baslangic, $bitis);
        $this->isDirty = true;
    }

    // ─────────────────────────────────────────────────────────────
    // Persistence
    // ─────────────────────────────────────────────────────────────

    /**
     * Persist aggregate to database.
     */
    public function persist(): CommercialOffering
    {
        $data = [
            'uuid' => $this->uuid,
            'idempotency_key' => $this->idempotencyKey,
            'tenant_id' => $this->tenantId,
            'workspace_id' => $this->workspaceId,
            'property_id' => $this->propertyId,
            'offering_type' => $this->offeringType->value,
            'fiyat' => $this->price->getAmount(),
            'para_birimi' => $this->price->getCurrency(),
            'depozito' => $this->price->getDepozito(),
            'komisyon_orani' => $this->commission->getRate(),
            'baslangic_tarihi' => $this->dateRange->getBaslangicTarihi()?->format('Y-m-d'),
            'bitis_tarihi' => $this->dateRange->getBitisTarihi()?->format('Y-m-d'),
            'yayin_durumu' => $this->state->value,
        ];

        // Check if we already have a persisted ID
        if ($this->persistedId !== null) {
            $model = CommercialOffering::find($this->persistedId);
            if ($model && $this->isDirty) {
                // Only update if the aggregate was modified (dirty)
                $model->update($data);
            }
        } else {
            // Check if record exists by idempotency key
            $existing = CommercialOffering::where('idempotency_key', $this->idempotencyKey)->first();

            if ($existing) {
                // Update existing record
                $existing->update($data);
                $model = $existing;
                $this->persistedId = $model->id;
            } else {
                // Create new record with uuid
                $data['uuid'] = $this->uuid;
                $model = CommercialOffering::create($data);
                $this->persistedId = $model->id;
            }
        }

        $this->isDirty = false;

        return $model;
    }

    /**
     * Get domain events for dispatching.
     */
    public function pullDomainEvents(): array
    {
        return [];
    }

    // ─────────────────────────────────────────────────────────────
    // Serialization
    // ─────────────────────────────────────────────────────────────

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenantId,
            'workspace_id' => $this->workspaceId,
            'property_id' => $this->propertyId,
            'offering_type' => $this->offeringType->value,
            'price' => $this->price->toArray(),
            'commission' => $this->commission->getRate(),
            'date_range' => $this->dateRange->toArray(),
            'state' => $this->state->value,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Private Constructor
    // ─────────────────────────────────────────────────────────────

    private function __construct() {}
}

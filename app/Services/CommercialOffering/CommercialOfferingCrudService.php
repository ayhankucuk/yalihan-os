<?php

namespace App\Services\CommercialOffering;

use App\Domain\CommercialOffering\CommercialOfferingAggregate;
use App\Domain\CommercialOffering\Enums\OfferingState;
use App\Domain\CommercialOffering\Enums\OfferingType;
use App\Domain\CommercialOffering\Exceptions\CommercialOfferingDomainException;
use App\Domain\CommercialOffering\ValueObjects\Commission;
use App\Domain\CommercialOffering\ValueObjects\DateRange;
use App\Domain\Shared\ValueObjects\Money;
use App\Models\CommercialOffering;
use App\Models\Property;
use App\Models\WorkforceExecution;
use App\Repositories\CommercialOfferingRepositoryInterface;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CommercialOffering CRUD Service.
 *
 * Uses CommercialOfferingAggregate for domain logic.
 * Enforces tenant isolation and business invariants.
 */
class CommercialOfferingCrudService
{
    public function __construct(
        private CommercialOfferingRepositoryInterface $repository,
        private TenantContextService $tenantContext
    ) {}

    /**
     * Create a new CommercialOffering.
     *
     * @throws CommercialOfferingDomainException
     */
    public function create(Property $property, array $data): CommercialOffering
    {
        // Validate: Same type cannot have another ACTIVE offering
        $offeringType = OfferingType::from($data['offering_type'] ?? 'SATILIK');

        if (isset($data['idempotency_key'])) {
            // Idempotent: Check if record already exists using raw query
            $existingModel = \Illuminate\Support\Facades\DB::table('commercial_offerings')
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($existingModel) {
                // Use withoutTenant to bypass scope and return existing record
                return \App\Models\CommercialOffering::withoutTenant()->findOrFail((int) $existingModel->id);
            }
        }

        $this->validatePropertyAccess($property);

        $money = new Money(
            (float) ($data['fiyat'] ?? 0),
            $data['para_birimi'] ?? 'TRY'
        );

        $commission = isset($data['komisyon_orani'])
            ? new Commission((float) $data['komisyon_orani'])
            : new Commission(null);

        $dateRange = null;
        if (isset($data['baslangic_tarihi']) || isset($data['bitis_tarihi'])) {
            $dateRange = new DateRange(
                isset($data['baslangic_tarihi']) ? new \DateTimeImmutable($data['baslangic_tarihi']) : null,
                isset($data['bitis_tarihi']) ? new \DateTimeImmutable($data['bitis_tarihi']) : null
            );
        }

        $aggregate = CommercialOfferingAggregate::create(
            tenantId: $property->tenant_id,
            workspaceId: $property->workspace_id,
            propertyId: $property->id,
            offeringType: $offeringType,
            price: $money,
            commission: $commission,
            dateRange: $dateRange,
            idempotencyKey: $data['idempotency_key'] ?? null
        );

        return DB::transaction(function () use ($aggregate, $data, $property) {
            $model = $aggregate->persist();

            $this->logExecution(
                $property->tenant_id,
                $property->workspace_id,
                $aggregate->getUuid(),
                'create_offering',
                $data,
                ['offering_id' => $model->id, 'uuid' => $model->uuid],
                $model->id
            );

            return $model->fresh();
        });
    }

    /**
     * Activate a CommercialOffering (DRAFT → ACTIVE).
     *
     * @throws CommercialOfferingDomainException
     */
    public function activate(int $offeringId): CommercialOffering
    {
        $aggregate = $this->loadAndValidate($offeringId);

        // Business rule: Only one ACTIVE per type per property
        $existing = $this->repository->findActiveByPropertyAndType(
            $aggregate->getPropertyId(),
            $aggregate->getOfferingType()
        );

        if ($existing && $existing->getUuid() !== $aggregate->getUuid()) {
            throw CommercialOfferingDomainException::duplicateActiveOffering(
                $aggregate->getOfferingType()->value
            );
        }

        $aggregate->activate();
        $model = $aggregate->persist();

        $this->logExecution(
            $aggregate->getTenantId(),
            $aggregate->getWorkspaceId(),
            $aggregate->getUuid(),
            'activate_offering',
            [],
            ['yayin_durumu' => 'ACTIVE'],
            $model->id
        );

        return $model->fresh();
    }

    /**
     * Archive a CommercialOffering (ANY → ARCHIVED).
     * ARCHIVED is a terminal state.
     *
     * @throws CommercialOfferingDomainException
     */
    public function archive(int $offeringId): CommercialOffering
    {
        $aggregate = $this->loadAndValidate($offeringId);

        $aggregate->archive();
        $model = $aggregate->persist();

        $this->logExecution(
            $aggregate->getTenantId(),
            $aggregate->getWorkspaceId(),
            $aggregate->getUuid(),
            'archive_offering',
            [],
            ['yayin_durumu' => 'ARCHIVED'],
            $model->id
        );

        return $model->fresh();
    }

    /**
     * Update price.
     *
     * @throws CommercialOfferingDomainException
     */
    public function updatePrice(int $offeringId, Money $newPrice): CommercialOffering
    {
        $aggregate = $this->loadAndValidate($offeringId);

        $oldPrice = $aggregate->getMoney();
        $aggregate->updatePrice($newPrice);
        $model = $aggregate->persist();

        $this->logExecution(
            $aggregate->getTenantId(),
            $aggregate->getWorkspaceId(),
            $aggregate->getUuid(),
            'change_price',
            [
                'old_price' => $oldPrice->getAmount(),
                'new_price' => $newPrice->getAmount(),
                'currency' => $newPrice->getCurrency(),
            ],
            [],
            $model->id
        );

        return $model->fresh();
    }

    /**
     * Update offering data.
     *
     * @throws CommercialOfferingDomainException
     */
    public function update(int $offeringId, array $data): CommercialOffering
    {
        $aggregate = $this->loadAndValidate($offeringId);

        if (isset($data['komisyon_orani'])) {
            $aggregate->updateCommission((float) $data['komisyon_orani']);
        }

        if (isset($data['baslangic_tarihi']) || isset($data['bitis_tarihi'])) {
            $aggregate->updateDateRange(
                isset($data['baslangic_tarihi']) ? new \DateTimeImmutable($data['baslangic_tarihi']) : null,
                isset($data['bitis_tarihi']) ? new \DateTimeImmutable($data['bitis_tarihi']) : null
            );
        }

        if (isset($data['fiyat'])) {
            $money = new Money(
                (float) $data['fiyat'],
                $data['para_birimi'] ?? $aggregate->getPrice()->getCurrency()
            );
            $aggregate->updatePrice($money);
        }

        $model = $aggregate->persist();

        $this->logExecution(
            $aggregate->getTenantId(),
            $aggregate->getWorkspaceId(),
            $aggregate->getUuid(),
            'update_offering',
            $data,
            [],
            $model->id
        );

        return $model->fresh();
    }

    /**
     * Soft delete offering. Must be ARCHIVED first.
     *
     * @throws CommercialOfferingDomainException
     */
    public function delete(int $offeringId): void
    {
        $aggregate = $this->loadAndValidate($offeringId);

        if (!$aggregate->isArchived()) {
            throw CommercialOfferingDomainException::cannotDeleteActive();
        }

        $this->repository->delete($aggregate);

        $this->logExecution(
            $aggregate->getTenantId(),
            $aggregate->getWorkspaceId(),
            $aggregate->getUuid(),
            'delete_offering',
            [],
            [],
            $aggregate->getPropertyId() // Use propertyId as identifier since offering is deleted
        );
    }

    /**
     * Get all offerings for a property.
     *
     * @return CommercialOffering[]
     */
    public function getByProperty(int $propertyId): array
    {
        $tenant = $this->tenantContext->getTenant();
        $workspace = $this->tenantContext->getWorkspace();

        $property = Property::findOrFail($propertyId);

        $this->validateTenantAccess($property->tenant_id);

        if ($workspace) {
            $this->validateWorkspaceAccess($property->workspace_id);
        }

        return CommercialOffering::where('property_id', $propertyId)
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * Get offering by ID.
     */
    public function findById(int $offeringId): ?CommercialOffering
    {
        $offering = CommercialOffering::find($offeringId);

        if ($offering) {
            $this->validateTenantAccess($offering->tenant_id);

            $workspace = $this->tenantContext->getCurrentWorkspace();
            if ($workspace) {
                $this->validateWorkspaceAccess($offering->workspace_id);
            }
        }

        return $offering;
    }

    // ─────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────

    private function loadAndValidate(int $offeringId): CommercialOfferingAggregate
    {
        $aggregate = $this->repository->findById($offeringId);

        if (!$aggregate) {
            throw new \RuntimeException("CommercialOffering #{$offeringId} not found.");
        }

        $this->validateTenantAccess($aggregate->getTenantId());
        $this->validateWorkspaceAccess($aggregate->getWorkspaceId());

        return $aggregate;
    }

    private function validatePropertyAccess(Property $property): void
    {
        $this->validateTenantAccess($property->tenant_id);

        $workspace = $this->tenantContext->getWorkspace();
        if ($workspace) {
            $this->validateWorkspaceAccess($property->workspace_id);
        }
    }

    private function validateTenantAccess(int $offeringTenantId): void
    {
        $tenant = $this->tenantContext->getTenant();

        if (!$tenant) {
            throw new \RuntimeException('Tenant context is required.');
        }

        if ($tenant->id !== $offeringTenantId) {
            throw new \RuntimeException(
                "Cross-tenant access denied. Offering belongs to tenant {$offeringTenantId}, current context is {$tenant->id}."
            );
        }
    }

    private function validateWorkspaceAccess(int $offeringWorkspaceId): void
    {
        $workspace = $this->tenantContext->getWorkspace();

        if ($workspace && $workspace->id !== $offeringWorkspaceId) {
            throw new \RuntimeException(
                "Cross-workspace access denied. Offering belongs to workspace {$offeringWorkspaceId}, current context is {$workspace->id}."
            );
        }
    }

    private function logExecution(
        int $tenantId,
        int $workspaceId,
        string $uuid,
        string $capability,
        array $input,
        array $result,
        ?int $aggregateId = null
    ): void {
        WorkforceExecution::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'workspace_id' => $workspaceId,
            'aggregate_type' => 'CommercialOffering',
            'aggregate_id' => $aggregateId,
            'capability' => $capability,
            'execution_status' => 'SUCCESS',
            'started_at' => now(),
            'finished_at' => now(),
            'input_snapshot' => $input,
            'result_snapshot' => $result,
        ]);
    }
}

<?php

namespace App\Services\Property;

use App\Models\CommercialOffering;
use App\Models\Property;
use App\Models\WorkforceExecution;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Property\Events\CommercialOfferingCreated;
use App\Domain\Property\Events\CommercialOfferingActivated;
use App\Domain\Property\Events\CommercialOfferingArchived;
use App\Domain\Property\Events\CommercialOfferingPriceChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DomainException;

class CommercialOfferingService
{
    /**
     * Creates a new Commercial Offering for a Property within a Workspace.
     * Uses Money Value Object for decimal precision pricing and dispatches CommercialOfferingCreated event.
     */
    public function createOffering(Property $property, array $data): CommercialOffering
    {
        $amount = $data['fiyat'] ?? 0;
        $currency = $data['para_birimi'] ?? 'TRY';
        $money = new Money((float) $amount, $currency);

        return DB::transaction(function () use ($property, $data, $money) {
            $offering = CommercialOffering::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $property->tenant_id,
                'workspace_id' => $property->workspace_id,
                'property_id' => $property->id,
                'offering_type' => $data['offering_type'] ?? 'SATILIK',
                'fiyat' => $money->getAmount(),
                'para_birimi' => $money->getCurrency(),
                'komisyon_orani' => $data['komisyon_orani'] ?? null,
                'depozito' => $data['depozito'] ?? null,
                'baslangic_tarihi' => $data['baslangic_tarihi'] ?? null,
                'bitis_tarihi' => $data['bitis_tarihi'] ?? null,
                'yayin_durumu' => 'DRAFT',
            ]);

            // Execution Audit Log
            WorkforceExecution::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $property->tenant_id,
                'workspace_id' => $property->workspace_id,
                'aggregate_type' => 'CommercialOffering',
                'aggregate_id' => $offering->id,
                'capability' => 'create_offering',
                'execution_status' => 'SUCCESS',
                'started_at' => now(),
                'finished_at' => now(),
                'input_snapshot' => $data,
                'result_snapshot' => [
                    'offering_id' => $offering->id,
                    'uuid' => $offering->uuid,
                    'fiyat' => $offering->fiyat,
                ],
            ]);

            CommercialOfferingCreated::dispatch($offering);

            return $offering;
        });
    }

    /**
     * Activates a Commercial Offering (State Transition: DRAFT -> ACTIVE).
     */
    public function activateOffering(CommercialOffering $offering): CommercialOffering
    {
        if ($offering->yayin_durumu === 'ARCHIVED') {
            throw new DomainException('Cannot activate an archived Commercial Offering.');
        }

        $offering->yayin_durumu = 'ACTIVE';
        $offering->save();

        // Execution Audit Log
        WorkforceExecution::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $offering->tenant_id,
            'workspace_id' => $offering->workspace_id,
            'aggregate_type' => 'CommercialOffering',
            'aggregate_id' => $offering->id,
            'capability' => 'activate_offering',
            'execution_status' => 'SUCCESS',
            'started_at' => now(),
            'finished_at' => now(),
            'result_snapshot' => [
                'offering_id' => $offering->id,
                'yayin_durumu' => 'ACTIVE',
            ],
        ]);

        CommercialOfferingActivated::dispatch($offering);

        return $offering;
    }

    /**
     * Archives a Commercial Offering (State Transition: ANY -> ARCHIVED).
     */
    public function archiveOffering(CommercialOffering $offering): CommercialOffering
    {
        $offering->yayin_durumu = 'ARCHIVED';
        $offering->save();

        // Execution Audit Log
        WorkforceExecution::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $offering->tenant_id,
            'workspace_id' => $offering->workspace_id,
            'aggregate_type' => 'CommercialOffering',
            'aggregate_id' => $offering->id,
            'capability' => 'archive_offering',
            'execution_status' => 'SUCCESS',
            'started_at' => now(),
            'finished_at' => now(),
            'result_snapshot' => [
                'offering_id' => $offering->id,
                'yayin_durumu' => 'ARCHIVED',
            ],
        ]);

        CommercialOfferingArchived::dispatch($offering);

        return $offering;
    }

    /**
     * Updates the price of a Commercial Offering using Money Value Object and dispatches PriceChanged event.
     */
    public function changePrice(CommercialOffering $offering, Money $newPrice): CommercialOffering
    {
        $oldPrice = $offering->getMoney();

        if ($oldPrice->equals($newPrice)) {
            return $offering;
        }

        $offering->setMoney($newPrice);
        $offering->save();

        // Execution Audit Log
        WorkforceExecution::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $offering->tenant_id,
            'workspace_id' => $offering->workspace_id,
            'aggregate_type' => 'CommercialOffering',
            'aggregate_id' => $offering->id,
            'capability' => 'change_price',
            'execution_status' => 'SUCCESS',
            'started_at' => now(),
            'finished_at' => now(),
            'input_snapshot' => [
                'old_price' => $oldPrice->getAmount(),
                'new_price' => $newPrice->getAmount(),
                'currency' => $newPrice->getCurrency(),
            ],
        ]);

        CommercialOfferingPriceChanged::dispatch($offering, $oldPrice, $newPrice);

        return $offering;
    }
}

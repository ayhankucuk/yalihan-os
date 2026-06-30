<?php

namespace App\Actions\Admin\Calendar;

use App\Models\PropertyAvailability;

class BlockCalendarDatesAction
{
    public function handle(int $ilanId, array $dates, ?string $reason = null): array
    {
        $blocked = [];
        foreach ($dates as $date) {
            $block = PropertyAvailability::updateOrCreate(
                [
                    'property_id' => $ilanId,
                    'date' => $date,
                ],
                [
                    'is_available' => false,
                    'block_reason' => $reason ?? 'Manuel engelleme',
                    'source_system' => 'internal',
                ]
            );

            $blocked[] = $block;
        }

        return $blocked;
    }
}

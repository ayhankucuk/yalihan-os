<?php

namespace App\Services\ChannelManager;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ChannexWebhookTenantResolver
 * CHANNEL_MANAGER_PROVIDER Wave 2 — ADR-007
 * Resolves tenant_id from Channex property_id via IlanTakvimSync (DB join, bypasses global scopes).
 */
class ChannexWebhookTenantResolver
{
    public function resolveFromPropertyId(string $channexPropertyId): ?int
    {
        if (empty($channexPropertyId)) {
            return null;
        }
        $row = DB::table('ilan_takvim_sync as s')
            ->join('ilanlar as i', 'i.id', '=', 's.ilan_id')
            ->where('s.external_listing_id', $channexPropertyId)
            ->where('s.is_sync_active', true)
            ->whereNotNull('i.tenant_id')
            ->select('i.tenant_id')
            ->first();
        if ($row === null) {
            Log::warning('ChannexWebhookTenantResolver: no active sync config', ['property_id' => $channexPropertyId]);
            return null;
        }
        return (int) $row->tenant_id;
    }

    public function resolveIlanId(string $channexPropertyId, int $tenantId): ?int
    {
        $row = DB::table('ilan_takvim_sync as s')
            ->join('ilanlar as i', 'i.id', '=', 's.ilan_id')
            ->where('s.external_listing_id', $channexPropertyId)
            ->where('s.is_sync_active', true)
            ->where('i.tenant_id', $tenantId)
            ->select('s.ilan_id')
            ->first();
        return $row ? (int) $row->ilan_id : null;
    }
}

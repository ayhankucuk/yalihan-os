<?php

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use App\Models\GovernanceIncident;

class GovIncidentReplay extends Command
{
    protected $signature = 'gov:incident:replay
                            {--tenant= : The tenant ID to list incidents for}
                            {--limit=20 : Number of incidents to show}';

    protected $description = 'List and Replay Governance Incidents for Forensic Analysis.';

    public function handle()
    {
        $tenantId = $this->option('tenant');
        $limit = $this->option('limit');

        $query = GovernanceIncident::orderBy('created_at', 'desc')->limit($limit);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $incidents = $query->get();

        if ($incidents->isEmpty()) {
            $this->info("✅ No incidents found" . ($tenantId ? " for tenant [{$tenantId}]" : ""));
            return 0;
        }

        $this->table(
            ['ID', 'Time', 'Tenant', 'Type', 'Source', 'Risk', 'Details'],
            $incidents->map(fn($i) => [
                $i->id,
                $i->created_at->toDateTimeString(),
                $i->tenant_id,
                $i->olay_tipi,
                $i->kaynak,
                $i->risk_seviyesi,
                json_encode($i->details) // Truncate if too long in real implementation
            ])
        );

        return 0;
    }
}

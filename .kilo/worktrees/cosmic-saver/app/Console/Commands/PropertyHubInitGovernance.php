<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use Illuminate\Support\Str;

class PropertyHubInitGovernance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bekci:governance:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Establish the first governed configuration baseline (ACTIVE snapshot)';

    /**
     * Execute the console command.
     */
    public function handle(ConfigSnapshotService $snapshotService)
    {
        $this->info('🛡️ PropertyHub Governance Initialization starting...');

        $activeVersion = PropertyConfigVersion::where('governance_state', 'ACTIVE')->first();

        if ($activeVersion) {
            $this->warn('Current ACTIVE version found: ' . $activeVersion->version_hash);

            if ($activeVersion->snapshot_json) {
                $this->info('✅ ACTIVE version already has a valid snapshot.');
                return;
            }

            $this->info('📸 Capturing snapshot for the current ACTIVE version...');
            $snapshot = $snapshotService->capture();
            $version->update([
                'snapshot_json' => $snapshot,
                'signature' => ConfigSnapshotService::computeSignature($snapshot)
            ]);
            $this->info('✅ Snapshot captured and signed.');
            return;
        }

        $this->info('🚀 No ACTIVE version found. Creating a new Baseline Version...');

        $baseline = PropertyConfigVersion::create([
            'version_hash' => Str::random(64),
            'governance_state' => 'ACTIVE',
            'description' => 'System Baseline: Initial Governed Snapshot',
            'applied_at' => now(),
            'active_flag' => 1,
        ]);

        $this->info('📸 Capturing system snapshot...');
        $snapshot = $snapshotService->capture();
        $signature = ConfigSnapshotService::computeSignature($snapshot);
        $baseline->update([
            'snapshot_json' => $snapshot,
            'signature' => $signature
        ]);

        $this->info('✅ Baseline version established and activated.');
        $this->table(['Field', 'Value'], [
            ['Version ID', $baseline->id],
            ['Version Hash', $baseline->version_hash],
            ['Signature', substr($signature, 0, 16) . '...'],
            ['State', 'ACTIVE (SSOT Locked)'],
        ]);
    }
}

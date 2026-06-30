<?php

namespace App\Console\Commands\Sab;

use Illuminate\Console\Command;
use App\Services\Governance\AuthorityMap\AuthoritySourceReader;
use App\Services\Governance\AuthorityMap\AuthorityProjectionBuilder;
use App\Services\Governance\AuthorityMap\AuthorityMapMarkdownFormatter;
use App\Services\Governance\AuthorityMap\AuthorityMapWriter;

class SabAuthorityMapGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sab:authority-map:generate
                            {--check : Check if existing projection matches source without writing}
                            {--write : Force overwrite the human-readable projection}
                            {--stdout : Output raw markdown to console without writing}
                            {--dry-run : Simulate generation and print domains to console}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate or check human-readable companion projection (docs/authority-map.md) from .sab/authority.json';

    public function handle(
        AuthoritySourceReader $reader,
        AuthorityProjectionBuilder $builder,
        AuthorityMapMarkdownFormatter $formatter,
        AuthorityMapWriter $writer
    ) {
        $check = $this->option('check');
        $write = $this->option('write');
        $stdout = $this->option('stdout');
        $dryRun = $this->option('dry-run');

        // Default safety mode (no write flag passed)
        if (!$check && !$write && !$stdout && !$dryRun) {
            $this->warn('Safety Mode Active: No action taken.');
            $this->info('This command generates the human-readable projection for .sab/authority.json.');
            $this->info('Use --check, --write, --stdout, or --dry-run flags to execute.');
            return 0;
        }

        try {
            // Read source
            $rawJson = $reader->read();

            // Build domains
            $projectData = $builder->build($rawJson);

            // Format markdown
            $markdown = $formatter->format($projectData);

            if ($dryRun) {
                $this->info('Dry-run mode. Generating from domains:');
                foreach ($projectData['domains'] as $domain) {
                    $this->line("- {$domain['title']}");
                }
                return 0;
            }

            if ($stdout) {
                // Raw output for agent consumption
                $this->output->write($markdown);
                return 0;
            }

            if ($check) {
                if ($writer->check($markdown)) {
                    $this->info('✅ Projection is in sync with source (No drift).');
                    return 0;
                } else {
                    $this->error('❌ Drift detected! docs/authority-map.md does not match the source generated fields.');
                    // The user asked about printing a patch/diff in the open questions. For now just text.
                    $this->line('Run with --write to sync the projection.');
                    return 1;
                }
            }

            if ($write) {
                $writer->write($markdown);
                $this->info('✨ docs/authority-map.md generated successfully.');
                return 0;
            }

        } catch (\Exception $e) {
            $this->error('Execution Error: ' . $e->getMessage());
            return 2;
        }
    }
}

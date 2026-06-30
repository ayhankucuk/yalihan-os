<?php

declare(strict_types=1);

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BekciPatternLearnCommand extends Command
{
    protected $signature = 'bekci:pattern:learn
        {name : Human-readable name of the pattern}
        {signature : The string/regex pattern to look for}
        {--id= : Optional custom ID (LP-XXX)}
        {--desc= : Optional description}';

    protected $description = 'Teach Yalıhan Bekçi a new anti-pattern or regression signature';

    public function handle(): int
    {
        $name = $this->argument('name');
        $signature = $this->argument('signature');
        $desc = $this->option('desc') ?? "Newly learned pattern.";
        
        $filePath = base_path('docs/governance/LEARNED_PATTERNS.json');
        
        if (!File::exists($filePath)) {
            $this->error("LEARNED_PATTERNS.json not found.");
            return Command::FAILURE;
        }

        $data = json_decode(File::get($filePath), true);
        
        $newId = $this->option('id') ?? ('LP-' . str_pad((string)(count($data['patterns']) + 1), 3, '0', STR_PAD_LEFT));
        
        $data['patterns'][] = [
            'id' => $newId,
            'name' => $name,
            'signature' => $signature,
            'first_detected' => date('Y-m-d'),
            'status' => 'ENFORCED',
            'description' => $desc
        ];
        
        $data['total_learned'] = count($data['patterns']);
        
        File::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        $this->info("🛡️ Yalıhan Bekçi has learned a new pattern: [{$newId}] {$name}");
        $this->comment("Signature: {$signature}");
        
        return Command::SUCCESS;
    }
}

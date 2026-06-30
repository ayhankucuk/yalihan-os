<?php

namespace App\Console\Commands;

use App\Services\Bekci\Scanners\AestheticsScanner;
use Illuminate\Console\Command;

class BekciAestheticsCommand extends Command
{
    protected $signature = 'bekci:aesthetics {--file= : Belirli bir dosyayı tara}';
    protected $description = 'Yalıhan Bekçi: UI/UX Estetik ve Dark Mode Denetimi';

    public function handle(AestheticsScanner $scanner): int
    {
        $this->info('🎨 Yalıhan Bekçi: Estetik Denetimi Başlatılıyor...');

        $file = $this->option('file');

        if ($file) {
            $violations = $scanner->scanFile(base_path($file));
            $results = [$file => $violations];
        } else {
            $results = $scanner->scan();
        }

        if (empty(array_filter($results))) {
            $this->info('✅ Tüm tasarım kuralları ve Dark Mode uyumluluğu mühürlü!');
            return 0;
        }

        foreach ($results as $filePath => $violations) {
            if (empty($violations)) continue;

            $this->warn("\n📂 Dosya: {$filePath}");
            foreach ($violations as $v) {
                $this->line("   [Satır {$v['line']}] <fg=yellow>{$v['message']}</>");
                if (isset($v['suggestion'])) {
                    $this->line("   💡 Öneri: {$v['suggestion']}");
                }
            }
        }

        return 1;
    }
}

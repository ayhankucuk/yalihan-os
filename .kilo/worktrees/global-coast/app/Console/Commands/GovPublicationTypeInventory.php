<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Support\YayinTipiRules;
use App\Services\Logging\LogService;

class GovPublicationTypeInventory extends Command
{
    protected $signature = 'gov:publication-type-inventory';
    protected $description = 'Build publication type inventory report and write to docs/GOV_PUBLICATION_TYPE_INVENTORY.md';

    public function handle(): int
    {
        $t0 = LogService::startTimer('gov_inventory_build');
        $rows = DB::table('yayin_tipi_sablonlari')
            ->select('yayin_tipi', DB::raw('COUNT(*) as c'))
            ->groupBy('yayin_tipi')
            ->orderBy('yayin_tipi')
            ->get();

        $lines = [];
        $lines[] = '# GOV Publication Type Inventory';
        $lines[] = '';
        $lines[] = '| yayin_tipi | count | class | canonical | calendar? | note |';
        $lines[] = '|---|---:|---|---|---|---|';

        foreach ($rows as $r) {
            $raw = (string) $r->yayin_tipi;
            $count = (int) $r->c;
            $class = $this->classify($raw);
            $canonical = null;
            $calendar = 'false';
            $note = '';
            try {
                $canonical = YayinTipiRules::canonicalizeSlug($raw);
                $calendar = YayinTipiRules::requiresCalendar($canonical) ? 'true' : 'false';
            } catch (\Throwable $e) {
                $note = 'UNKNOWN';
            }
            $lines[] = sprintf('| %s | %d | %s | %s | %s | %s |',
                $raw, $count, $class, $canonical ?? 'NULL', $calendar, $note
            );
        }

        $path = base_path('docs/GOV_PUBLICATION_TYPE_INVENTORY.md');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);

        LogService::info('gov_inventory_build', [
            'result' => 'success',
            'rows' => count($rows),
            'duration_ms' => (int) LogService::stopTimer($t0),
        ]);
        $this->info('Report written: docs/GOV_PUBLICATION_TYPE_INVENTORY.md');
        return Command::SUCCESS;
    }

    private function classify(string $raw): string
    {
        $s = strtolower(trim($raw));
        $s = str_replace(['ı','ğ','ü','ş','ö','ç'], ['i','g','u','s','o','c'], $s);
        $s = preg_replace('/[^a-z0-9\- ]/', '', $s);
        if (in_array($s, ['satilik','kiralik','devren','kat karsiligi','kat-karsiligi'], true)) return 'TRANSACTION';
        if (str_contains($s, 'gunluk') || str_contains($s, 'haftalik') || str_contains($s, 'aylik') || str_contains($s, 'sezonluk')) return 'TERM';
        foreach (['kredi','yatirim','luks','sahibinden','acil','trampali','on satis','insaat','sifir','yeni','sosyal','ogrenci','ofis','ihale'] as $h) {
            if (str_contains($s, $h)) return 'BADGE';
        }
        if (str_contains($s, 'yazlik')) return 'SEGMENT-LEAK';
        if (str_contains($s, 'devren ')) return 'ALIAS/LEGACY';
        return 'UNKNOWN';
    }
}


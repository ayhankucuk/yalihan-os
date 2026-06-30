<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Logging\LogService;

class GovBadgeMigrate extends Command
{
    protected $signature = 'gov:badge-migrate {--apply}';
    protected $description = 'Identify badge-like publication types and prepare migration to UPS Feature (dry-run/apply)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $t0 = LogService::startTimer('gov_badge_migrate');

        $candidates = DB::table('yayin_tipi_sablonlari')
            ->select('id', 'yayin_tipi', 'aktiflik_durumu')
            ->get();

        $badges = [];
        foreach ($candidates as $r) {
            $s = strtolower($r->yayin_tipi);
            if ($this->looksBadge($s)) {
                $usage = DB::table('ilanlar')->where('yayin_tipi_id', $r->id)->count();
                $badges[] = [
                    'id' => $r->id,
                    'label' => $r->yayin_tipi,
                    'aktiflik_durumu' => (int)$r->aktiflik_durumu,
                    'usage' => (int)$usage,
                ];
            }
        }

        if (!$apply) {
            $this->info('Dry-run: badge candidates');
            foreach ($badges as $b) {
                $this->line(sprintf('ID=%d label="%s" aktiflik_durumu=%d usage=%d', $b['id'], $b['label'], $b['aktiflik_durumu'], $b['usage']));
            }
            LogService::info('gov_badge_migrate', [
                'result' => 'dry_run',
                'badge_candidates' => count($badges),
                'duration_ms' => (int) LogService::stopTimer($t0),
            ]);
            return Command::SUCCESS;
        }

        $softDisabled = 0;
        foreach ($badges as $b) {
            if ($b['usage'] === 0 && $b['aktiflik_durumu'] !== 0) {
                DB::table('yayin_tipi_sablonlari')->where('id', $b['id'])->update(['aktiflik_durumu' => 0]);
                $softDisabled++;
            }
        }

        LogService::info('gov_badge_migrate', [
            'result' => 'success',
            'soft_disabled' => $softDisabled,
            'duration_ms' => (int) LogService::stopTimer($t0),
        ]);
        $this->info('Soft-disabled badges: ' . $softDisabled);
        return Command::SUCCESS;
    }

    private function looksBadge(string $s): bool
    {
        foreach (['kredi', 'yatirim', 'luks', 'sahibinden', 'acil', 'trampa', 'on satis', 'insaat', 'sifir', 'yeni', 'sosyal', 'ogrenci', 'ofis', 'ihale'] as $h) {
            if (str_contains($s, $h)) return true;
        }
        return false;
    }
}


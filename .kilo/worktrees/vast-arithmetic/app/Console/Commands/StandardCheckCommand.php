<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class StandardCheckCommand extends Command
{
    protected $signature = 'standard:check {--type=context7}';

    protected $description = 'Context7 standard check';

    public function handle(): int
    {
        $type = $this->option('type');
        $this->line('Context7 Standard Check');
        $this->line('Type: '.$type);

        $ilanlarOk = Schema::hasTable('ilanlar') ? 'OK' : 'MISSING';
        $this->line('Table ilanlar: '.$ilanlarOk);

        $idxNames = [
            'idx_ilanlar_il',
            'idx_ilanlar_ulke',
            'idx_ilanlar_ana_kategori',
            'idx_ilanlar_citizenship',
            'idx_ilanlar_fiyat',
            'idx_ilanlar_ana_il',
            'idx_ilanlar_ulke_fiyat',
        ];
        foreach ($idxNames as $name) {
            $exists = false;
            if (Schema::hasTable('ilanlar')) {
                $rows = DB::select('SHOW INDEX FROM ilanlar WHERE Key_name = ?', [$name]);
                $exists = ! empty($rows);
            }
            $this->line('Index '.$name.': '.($exists ? 'OK' : 'MISSING'));
        }

        $citizenship = Config::get('citizenship.programs', []);
        $this->line('Citizenship programs: '.(count($citizenship) > 0 ? 'OK' : 'MISSING'));

        $routes = [
            'admin.ilanlar.international.cache.clear',
            'admin.ilanlar.mcp.run',
            'ilanlar.international',
            'ilanlar.index',
        ];
        foreach ($routes as $r) {
            $this->line('Route '.$r.': '.(Route::has($r) ? 'OK' : 'MISSING'));
        }

        $bladePath = resource_path('views/frontend/ilanlar/international.blade.php');
        $this->line('Blade international: '.(File::exists($bladePath) ? 'OK' : 'MISSING'));

        $this->line('Completed');

        return Command::SUCCESS;
    }
}

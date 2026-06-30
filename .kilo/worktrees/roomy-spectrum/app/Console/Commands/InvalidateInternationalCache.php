<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class InvalidateInternationalCache extends Command
{
    protected $signature = 'context7:cache:invalidate-international';

    protected $description = 'International sayfa için ülke/şehir/tür listesi cache temizliği';

    public function handle(): int
    {
        $keys = [
            'international_countries',
            'international_cities',
            'international_property_types',
        ];
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        $this->info('✅ International cache anahtarları temizlendi');

        return Command::SUCCESS;
    }
}

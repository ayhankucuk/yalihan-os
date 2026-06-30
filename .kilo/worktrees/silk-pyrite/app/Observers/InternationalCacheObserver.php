<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

class InternationalCacheObserver
{
    public function saved(): void
    {
        $this->clear();
    }

    public function deleted(): void
    {
        $this->clear();
    }

    protected function clear(): void
    {
        Cache::forget('international_countries');
        Cache::forget('international_cities');
        Cache::forget('international_property_types');
    }
}

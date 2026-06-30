<?php

namespace App\Actions\Setting;

use App\Models\Currency;
use App\Services\CurrencyControlService;

class ToggleCurrencySettingAction
{
    public function __construct(
        private readonly CurrencyControlService $currencyService
    ) {}

    public function handle(string $code, bool $active): bool
    {
        $curr = Currency::where('code', $code)->firstOrFail();
        return $this->currencyService->toggleAktiflik($curr, $active);
    }
}

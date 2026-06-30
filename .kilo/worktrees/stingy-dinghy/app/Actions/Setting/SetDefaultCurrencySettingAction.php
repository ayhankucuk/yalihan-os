<?php

namespace App\Actions\Setting;

use App\Models\Currency;
use App\Services\CurrencyControlService;

class SetDefaultCurrencySettingAction
{
    public function __construct(
        private readonly CurrencyControlService $currencyService
    ) {}

    public function handle(string $code): void
    {
        $curr = Currency::where('code', $code)->firstOrFail();
        $this->currencyService->setDefault($curr);
    }
}

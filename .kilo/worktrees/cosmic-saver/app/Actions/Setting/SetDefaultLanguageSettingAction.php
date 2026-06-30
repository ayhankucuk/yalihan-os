<?php

namespace App\Actions\Setting;

use App\Models\Language;
use App\Services\LocaleControlService;

class SetDefaultLanguageSettingAction
{
    public function __construct(
        private readonly LocaleControlService $localeService
    ) {}

    public function handle(string $code): void
    {
        $lang = Language::where('code', $code)->firstOrFail();
        $this->localeService->setDefault($lang);
    }
}

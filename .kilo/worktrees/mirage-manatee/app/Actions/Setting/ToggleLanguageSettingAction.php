<?php

namespace App\Actions\Setting;

use App\Models\Language;
use App\Services\LocaleControlService;

class ToggleLanguageSettingAction
{
    public function __construct(
        private readonly LocaleControlService $localeService
    ) {}

    public function handle(string $code, bool $active): bool
    {
        $lang = Language::where('code', $code)->firstOrFail();
        return $this->localeService->toggleAktiflik($lang, $active);
    }
}

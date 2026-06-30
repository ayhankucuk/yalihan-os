<?php

namespace App\Actions\Setting;

use App\Contracts\Settings\SettingsAuthorityInterface;

/**
 * 🛡️ SAB S1 — Bulk Update Action (Thin Delegate)
 * All write + cache logic delegated to SettingsAuthorityService.
 */
class BulkUpdateSettingAction
{
    public function __construct(
        private readonly SettingsAuthorityInterface $authority
    ) {}

    public function handle(array $settingsToUpdate): void
    {
        $this->authority->bulkUpdate($settingsToUpdate);
    }
}

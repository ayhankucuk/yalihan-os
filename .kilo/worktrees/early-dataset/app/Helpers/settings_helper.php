<?php

/**
 * Settings Helper Functions
 * 🛡️ SAB S1: Routed through ConfigurationRegistryInterface
 */

use App\Contracts\Settings\ConfigurationRegistryInterface;

if (! function_exists('setting')) {
    /**
     * Get setting value via canonical ConfigurationRegistry.
     *
     * @param  string  $key  Setting key
     * @param  mixed  $default  Default value
     * @return mixed
     */
    function setting($key, $default = null)
    {
        return app(ConfigurationRegistryInterface::class)->get($key, $default);
    }
}

if (! function_exists('setting_set')) {
    /**
     * Set setting value via canonical SettingsAuthority.
     *
     * @param  string  $key  Setting key
     * @param  mixed  $value  Setting value
     * @param  string  $group  Setting group
     * @param  string|null  $type  Value type
     * @param  string|null  $description  Description
     * @return void
     */
    function setting_set($key, $value, $group = 'general', $type = null, $description = null)
    {
        app(\App\Contracts\Settings\SettingsAuthorityInterface::class)->set($key, $value, $group, $type, $description);
    }
}

if (! function_exists('setting_group')) {
    /**
     * Get settings by group via canonical ConfigurationRegistry.
     *
     * @param  string  $group  Group name
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function setting_group($group)
    {
        return app(ConfigurationRegistryInterface::class)->getGroup($group);
    }
}

if (! function_exists('setting_groups')) {
    /**
     * Get all groups with counts via canonical ConfigurationRegistry.
     *
     * @return array
     */
    function setting_groups()
    {
        return app(ConfigurationRegistryInterface::class)->getGroups();
    }
}


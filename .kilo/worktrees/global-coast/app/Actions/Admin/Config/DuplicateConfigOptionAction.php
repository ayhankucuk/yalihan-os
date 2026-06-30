<?php

namespace App\Actions\Admin\Config;

use App\Models\ConfigOption;

class DuplicateConfigOptionAction
{
    public function handle(ConfigOption $original): ConfigOption
    {
        $duplicate = $original->replicate();
        $duplicate->label = ($original->label ?? $original->option_key) . ' (Kopya)';
        $duplicate->save();

        return $duplicate;
    }
}

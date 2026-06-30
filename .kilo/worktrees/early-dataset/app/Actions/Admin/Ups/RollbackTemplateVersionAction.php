<?php

namespace App\Actions\Admin\Ups;

use App\Models\IlanTemplate;

class RollbackTemplateVersionAction
{
    public function handle(IlanTemplate $template, array $snapshot): bool
    {
        return $template->update($snapshot);
    }
}

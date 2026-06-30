<?php

namespace App\Actions\Admin\SiteApartman;

use App\Models\SiteApartman;

class DeleteSiteApartmanAction
{
    public function handle(SiteApartman $siteApartman): bool
    {
        return (bool) $siteApartman->delete();
    }
}

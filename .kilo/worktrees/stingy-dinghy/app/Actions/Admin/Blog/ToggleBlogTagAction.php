<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogTag;

class ToggleBlogTagAction
{
    public function handle(BlogTag $tag): BlogTag
    {
        $tag->update(['aktiflik_durumu' => ! (bool) $tag->aktiflik_durumu]);

        return $tag->fresh();
    }
}

<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogTag;

class DeleteBlogTagAction
{
    public function handle(BlogTag $tag): bool
    {
        return (bool) $tag->delete();
    }
}

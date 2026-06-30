<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogTag;

class UpdateBlogTagAction
{
    public function handle(BlogTag $tag, array $data): BlogTag
    {
        $tag->update($data);

        return $tag->fresh();
    }
}

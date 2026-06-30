<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogPost;

class SetBlogPostStickyStateAction
{
    public function handle(BlogPost $post, bool $sabit): BlogPost
    {
        $post->update(['sabit' => $sabit]);

        return $post->fresh();
    }
}

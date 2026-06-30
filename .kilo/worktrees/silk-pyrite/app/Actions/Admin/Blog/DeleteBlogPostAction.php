<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogPost;

class DeleteBlogPostAction
{
    public function handle(BlogPost $post): bool
    {
        return (bool) $post->delete();
    }
}

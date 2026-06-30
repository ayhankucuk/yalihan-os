<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogPost;

class UpdateBlogPostAction
{
    public function handle(BlogPost $post, array $data): BlogPost
    {
        $post->update($data);

        return $post->fresh();
    }
}

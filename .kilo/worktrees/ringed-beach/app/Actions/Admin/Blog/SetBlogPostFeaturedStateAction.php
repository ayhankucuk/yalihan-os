<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogPost;

class SetBlogPostFeaturedStateAction
{
    public function handle(BlogPost $post, bool $oneCikan): BlogPost
    {
        $post->update(['one_cikan' => $oneCikan]);

        return $post->fresh();
    }
}

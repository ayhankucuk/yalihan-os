<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogPost;

class SetBlogPostPublishStateAction
{
    public function handle(BlogPost $post, bool $yayinlandi): BlogPost
    {
        $post->update(['yayinlandi' => $yayinlandi]);

        return $post->fresh();
    }
}

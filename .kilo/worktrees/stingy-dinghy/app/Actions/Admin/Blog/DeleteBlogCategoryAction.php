<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogCategory;

class DeleteBlogCategoryAction
{
    public function handle(BlogCategory $category): bool
    {
        return (bool) $category->delete();
    }
}

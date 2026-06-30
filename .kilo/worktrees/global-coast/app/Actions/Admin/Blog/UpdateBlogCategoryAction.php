<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogCategory;

class UpdateBlogCategoryAction
{
    public function handle(BlogCategory $category, array $data): BlogCategory
    {
        $category->update($data);

        return $category->fresh();
    }
}

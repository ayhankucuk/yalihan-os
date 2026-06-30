<?php

namespace App\Actions\Admin\Blog;

use App\Models\BlogCategory;

class ToggleBlogCategoryAction
{
    public function handle(BlogCategory $category): BlogCategory
    {
        $category->update(['aktiflik_durumu' => ! (bool) $category->aktiflik_durumu]);

        return $category->fresh();
    }
}

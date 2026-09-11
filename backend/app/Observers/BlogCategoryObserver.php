<?php

namespace App\Observers;

use App\Models\BlogCategory;
use Illuminate\Support\Facades\Cache;

class BlogCategoryObserver
{
    public function saved(BlogCategory $blogCategory): void
    {
        Cache::tags(['blog'])->flush();
    }

    public function deleted(BlogCategory $blogCategory): void
    {
        Cache::tags(['blog'])->flush();
    }
}

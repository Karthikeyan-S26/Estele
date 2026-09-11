<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Redirect;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    public function saved(Category $category): void
    {
        Cache::forget('site.nav_categories');
        Cache::tags(['home', 'category:' . $category->id])->flush();
    }

    // Deliberately a separate updated() hook rather than checking
    // wasRecentlyCreated inside saved() — that flag stays true for the rest
    // of the request after the initial insert (documented Laravel behavior,
    // it does not reset on later saves), so it can't distinguish "just
    // created" from "updated later in the same request" the way it looks
    // like it should. updated() never fires on create, full stop.
    public function updated(Category $category): void
    {
        if ($category->wasChanged('slug') && $category->getOriginal('slug')) {
            Redirect::recordSlugChange(
                '/categories/'.$category->getOriginal('slug'),
                '/categories/'.$category->slug
            );
        }
    }

    public function deleted(Category $category): void
    {
        Cache::forget('site.nav_categories');
        Cache::tags(['home', 'category:' . $category->id])->flush();
    }
}

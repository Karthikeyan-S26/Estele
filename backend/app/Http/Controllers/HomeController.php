<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Collection as CollectionModel;
use App\Models\HomepageBlock;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $data = Cache::tags(['home'])->remember('home.page', now()->addHour(), function () {
            $data = [
                'categories' => Category::orderBy('sort_order')->with('media')->get(),
                'banners' => Banner::active()->ordered()->with('media')->get(),
                'homepageBlocks' => HomepageBlock::active()
                    ->ordered()
                    ->with(['items.media', 'items.itemable' => function ($morphTo) {
                        // morphWith, not a plain nested with(): 'blogCategory'
                        // only exists on Blog, and Faq has no media at all, so
                        // eager-loading either against every morph target throws.
                        $morphTo->morphWith([
                            Blog::class => ['media', 'blogCategory'],
                            Product::class => ['media'],
                            Category::class => ['media'],
                            CollectionModel::class => ['media'],
                        ]);
                    }])
                    ->get(),
            ];

            // product-card calls reviewsCount() and reviewsAverageRating() on
            // every card, so without this the grids cost two extra queries per
            // product. morphWith() can't carry aggregates, hence loading them
            // onto the already-hydrated Product models in one pass.
            $products = $data['homepageBlocks']
                ->flatMap->items
                ->pluck('itemable')
                ->filter(fn ($itemable) => $itemable instanceof Product)
                ->unique('id')
                ->values();

            // pluck() hands back a base Collection, which has no loadCount().
            (new EloquentCollection($products->all()))
                ->loadCount('approvedReviews')
                ->loadAvg('approvedReviews', 'rating');

            return $data;
        });

        return view('home.index', $data);
    }
}

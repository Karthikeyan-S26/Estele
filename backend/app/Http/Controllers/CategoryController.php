<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Cache::tags(['home'])->remember('categories.index', now()->addHour(), function () {
            return Category::orderBy('sort_order')->get();
        });

        return view('categories.index', compact('categories'));
    }

    public function show(Category $category, Request $request)
    {
        $sort = $request->query('sort', 'featured');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $inStock = $request->boolean('in_stock');
        $subcategorySlugs = array_values(array_filter((array) $request->query('subcategory', [])));
        $page = $request->query('page', 1);

        $category->loadMissing('children');

        $cacheKey = 'category.'.$category->id.'.products.'.md5(json_encode([
            $sort, $minPrice, $maxPrice, $inStock, $subcategorySlugs, $page,
        ]));

        $products = Cache::tags(['category:'.$category->id])->remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($category, $sort, $minPrice, $maxPrice, $inStock, $subcategorySlugs) {
                // Default (no subcategory filter applied): this category's own
                // products, plus its children's — matches estele.co's "Category"
                // facet, which folds subcategories into the parent listing.
                // A category with no children behaves exactly as before this
                // filter panel was added (single-id whereIn, same as the old
                // $category->products() pivot query).
                $categoryIds = [$category->id];
                if ($subcategorySlugs !== []) {
                    $matched = $category->children->whereIn('slug', $subcategorySlugs)->pluck('id')->all();
                    if ($matched !== []) {
                        $categoryIds = $matched;
                    }
                } elseif ($category->children->isNotEmpty()) {
                    $categoryIds = array_merge($categoryIds, $category->children->pluck('id')->all());
                }

                $query = Product::query()
                    ->with('media')
                    ->withCount('approvedReviews')
                    ->withAvg('approvedReviews', 'rating')
                    ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
                    ->where('is_active', true);

                if ($minPrice !== null && $minPrice !== '') {
                    $query->where('price', '>=', (float) $minPrice);
                }
                if ($maxPrice !== null && $maxPrice !== '') {
                    $query->where('price', '<=', (float) $maxPrice);
                }
                if ($inStock) {
                    // Mirrors Product::toSearchableArray()'s in_stock resolution:
                    // variant stock takes over once a product has variants.
                    $query->where(function ($q) {
                        $q->where(function ($plain) {
                            $plain->doesntHave('variants')->where('stock_quantity', '>', 0);
                        })->orWhereHas('variants', fn ($v) => $v->where('stock_quantity', '>', 0));
                    });
                }

                match ($sort) {
                    'price_asc' => $query->orderBy('price'),
                    'price_desc' => $query->orderBy('price', 'desc'),
                    'newest' => $query->latest('products.created_at'),
                    // 'featured' is the default sort and the storefront offers it by name,
                    // but it used to be indistinguishable from insertion order.
                    default => $query->orderByDesc('products.is_featured')->orderBy('products.id'),
                };

                return $query->paginate(24)->withQueryString();
            }
        );

        return view('categories.show', compact(
            'category', 'products', 'sort', 'minPrice', 'maxPrice', 'inStock', 'subcategorySlugs'
        ));
    }
}

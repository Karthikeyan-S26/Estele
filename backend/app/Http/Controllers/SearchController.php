<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'relevance');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $inStock = $request->boolean('in_stock');
        $categorySlugs = array_values(array_filter((array) $request->query('category', [])));

        $categories = Category::orderBy('name')->get(['id', 'name', 'slug']);

        if ($query === '') {
            $products = new LengthAwarePaginator([], 0, 24);
        } else {
            // Laravel Scout + MeiliSearch (spec §2/§5) — shouldBeSearchable() on Product
            // already restricts the index to is_active products, so no extra where() needed.
            $search = Product::search($query)->query(fn ($q) => $q->with('media'));

            if ($minPrice !== null && $minPrice !== '') {
                $search->where('price', '>=', (float) $minPrice);
            }
            if ($maxPrice !== null && $maxPrice !== '') {
                $search->where('price', '<=', (float) $maxPrice);
            }
            if ($inStock) {
                $search->where('in_stock', true);
            }
            if ($categorySlugs !== []) {
                $categoryIds = $categories->whereIn('slug', $categorySlugs)->pluck('id')->all();
                if ($categoryIds !== []) {
                    $search->whereIn('category_ids', $categoryIds);
                }
            }

            match ($sort) {
                'price_asc' => $search->orderBy('price', 'asc'),
                'price_desc' => $search->orderBy('price', 'desc'),
                'newest' => $search->orderBy('created_at', 'desc'),
                default => null, // 'relevance' — Meilisearch's own ranking, no explicit sort
            };

            $products = $search->paginate(24)->withQueryString();
        }

        return view('search.index', compact(
            'products', 'query', 'sort', 'minPrice', 'maxPrice', 'inStock', 'categories', 'categorySlugs'
        ));
    }

    /**
     * Autocomplete/suggestions dropdown for the header search bar. Deliberately
     * thin: no filters, no pagination — just the top handful of name/SKU matches
     * so people can jump straight to a product before submitting the full search.
     */
    public function suggest(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $products = Product::search($query)->take(6)->get();

        return response()->json([
            'results' => $products->map(fn (Product $product) => [
                'title' => $product->title,
                'url' => route('products.show', $product),
                'price' => (float) $product->price,
                'thumbnail' => $product->getFirstMediaUrl('gallery', 'card') ?: null,
            ])->values(),
        ]);
    }
}

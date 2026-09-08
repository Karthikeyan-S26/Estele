<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::active()->ordered()->with('media')->get();

        return view('collections.index', compact('collections'));
    }

    public function show(Collection $collection, Request $request)
    {
        abort_unless($collection->is_active, 404);

        $sort = $request->query('sort', 'featured');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $inStock = $request->boolean('in_stock');

        $query = $collection->products()->with('media')->where('is_active', true);

        if ($minPrice !== null && $minPrice !== '') {
            $query->where('price', '>=', (float) $minPrice);
        }
        if ($maxPrice !== null && $maxPrice !== '') {
            $query->where('price', '<=', (float) $maxPrice);
        }
        if ($inStock) {
            // Same in_stock resolution as CategoryController: variant stock
            // takes over once a product has variants.
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
            default => $query->orderBy('products.id'),
        };

        $products = $query->paginate(24)->withQueryString();

        return view('collections.show', compact(
            'collection', 'products', 'sort', 'minPrice', 'maxPrice', 'inStock'
        ));
    }
}

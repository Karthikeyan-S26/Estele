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

        $query = $collection->products()->with('media')->where('is_active', true);

        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->latest('products.created_at'),
            default => $query->orderBy('products.id'),
        };

        $products = $query->paginate(24)->withQueryString();

        return view('collections.show', compact('collection', 'products', 'sort'));
    }
}

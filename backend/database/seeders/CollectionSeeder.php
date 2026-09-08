<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\HomepageBlock;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Estele photography, stored locally in storage/app/seed-images —
        // distinct image per collection.
        $imagesDir = storage_path('app/seed-images/collections');

        $names = [
            ['name' => 'Hasli Collection', 'description' => 'Statement Hasli-style necklaces and sets.', 'image' => "{$imagesDir}/hasli-collection.png"],
            ['name' => 'Sitara Collection', 'description' => 'Studded stars for everyday sparkle.', 'image' => "{$imagesDir}/sitara-collection.png"],
            ['name' => 'Rose Collection', 'description' => 'Our signature rose gold plating, in every category.', 'image' => "{$imagesDir}/rose-collection.png"],
            ['name' => 'Crystal Blooms', 'description' => 'Floral crystal designs across the catalog.', 'image' => "{$imagesDir}/crystal-blooms.png"],
            ['name' => 'Colour Pop', 'description' => 'Bold coloured stones for a playful edit.', 'image' => "{$imagesDir}/colour-pop.png"],
            ['name' => 'Mor Bagh Collection', 'description' => 'Peacock-inspired designs, festive and refined.', 'image' => "{$imagesDir}/mor-bagh-collection.png"],
            // Maharani Collection is a real named collection from the original's own COLLECTIONS
            // dropdown — its banner art was originally (mis)used for "Featured Collection"; correcting
            // that here rather than just deleting it, since it's real, correctly-labeled Estele art.
            ['name' => 'Maharani Collection', 'description' => 'Regal, statement pieces fit for royalty.', 'image' => "{$imagesDir}/maharani-collection.png"],
            // Nav-only collections (not part of the "Shop by Collection" grid, same as the original) —
            // curated/query-based rather than a fixed even split, since these are editorial groupings,
            // not distinct product families, so overlap with the collections above is expected.
            ['name' => 'New Arrivals', 'description' => 'The latest additions to the catalog.', 'image' => "{$imagesDir}/new-arrivals.png", 'query' => fn () => Product::where('is_active', true)->latest()->take(10)->pluck('id')],
            ['name' => 'Wedding Season', 'description' => 'Bridal-ready pieces for the wedding season.', 'image' => "{$imagesDir}/wedding-season.png", 'query' => fn () => Product::where('is_active', true)->whereHas('categories', fn ($q) => $q->whereIn('slug', ['choker-sets', 'mangalsutra', 'necklace-sets']))->pluck('id')],
            ['name' => 'Best Seller', 'description' => 'Our most-loved pieces, hand-picked by the team.', 'image' => "{$imagesDir}/best-seller.png", 'query' => fn () => Product::where('is_active', true)->where('is_featured', true)->pluck('id')],
        ];

        // Remove any collections from an earlier, differently-scoped seed pass.
        $keepSlugs = collect($names)->map(fn ($n) => Str::slug($n['name']));
        Collection::whereNotIn('slug', $keepSlugs)->get()->each(fn (Collection $c) => $c->delete());

        // The first 6 (real product "families") get an even, non-overlapping split; the last 3
        // (editorial nav-only groupings) get their own real query instead, overlap allowed.
        $partitionedNames = array_filter($names, fn ($n) => ! isset($n['query']));
        $productIds = Product::where('is_active', true)->orderBy('id')->pluck('id');
        $chunks = $productIds->chunk((int) ceil($productIds->count() / count($partitionedNames)))->values();

        foreach ($names as $index => $data) {
            $collection = Collection::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );

            if (! $collection->hasMedia('image')) {
                try {
                    $collection->addMedia($data['image'])
                        ->preservingOriginal()
                        ->usingFileName($collection->slug.'.png')
                        ->toMediaCollection('image');
                } catch (\Throwable $e) {
                    $this->command?->warn("Could not fetch collection image for {$data['name']}: {$e->getMessage()}");
                }
            }

            $productIdsForCollection = isset($data['query']) ? $data['query']() : $chunks->get($index, collect());
            $collection->products()->sync($productIdsForCollection);
        }

        // The original's "Shop by Collection" grid only ever featured these 4 — Hasli/Sitara
        // were promoted via the hero banner + top-level nav only, never duplicated into this grid.
        $gridSlugs = ['rose-collection', 'crystal-blooms', 'colour-pop', 'mor-bagh-collection'];
        $collections = Collection::whereIn('slug', $gridSlugs)->orderBy('sort_order')->get();

        $block = HomepageBlock::firstOrCreate(
            ['type' => 'collection_carousel'],
            ['title' => 'Shop by Collection', 'sort_order' => 20, 'is_active' => true]
        );

        $block->items()->delete();
        foreach ($collections as $index => $collection) {
            $block->items()->create([
                'itemable_type' => Collection::class,
                'itemable_id' => $collection->id,
                'sort_order' => $index,
            ]);
        }
    }
}

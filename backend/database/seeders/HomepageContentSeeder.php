<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Collection as CollectionModel;
use App\Models\HomepageBlock;
use App\Models\Product;
use Illuminate\Database\Seeder;

class HomepageContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CollectionSeeder must run before this seeder (see DatabaseSeeder): the
        // rose / new-arrivals / best-seller collections drive the banner and the
        // three product carousels below. When they were missing, those sections
        // silently disappeared and Trending fell back to `is_featured`, so it
        // duplicated the Bestsellers row (the "repeated images" bug).
        // seedProductCarousel / seedNewArrivalsCarousel / seedBestsellersCarousel
        // are replaced by syncProductCarousel(), which also replaces stale
        // fallback items on an already-seeded database.
        $this->seedBanners();
        $this->seedCollectionBanner();
        $this->syncProductCarousel(title: null, sortOrder: 15, collectionSlug: 'trending');
        $this->syncProductCarousel(title: 'New Arrivals', sortOrder: 25, collectionSlug: 'new-arrivals');
        $this->syncProductCarousel(title: 'Bestsellers', sortOrder: 29, collectionSlug: 'best-seller', ctaLabel: 'View All');
        // seedPriceTiers() ("Your Budget, Your Bling") and seedNewsletter() ("Mail
        // Subscription") no longer called — those sections were removed from the
        // site (see HomepageBlockForm's type Select and home/index.blade.php's
        // render guard). Methods left below, unused, for the same reason those
        // two Blade block partials were left in place rather than deleted.
        $this->seedCelebrities();
        $this->seedUsp();
        $this->seedTestimonials();
        $this->seedBrandStory();
    }

    private function seedCollectionBanner(): void
    {
        $rose = CollectionModel::where('slug', 'rose-collection')->first();
        if (! $rose) {
            return;
        }

        $block = HomepageBlock::firstWhere('type', 'collection_banner');
        if ($block && $block->items()->exists()) {
            return;
        }

        // A previous run may have left a block without its item (e.g. an aborted
        // pre-collection seed), so create-or-repair rather than skip.
        $block ??= HomepageBlock::create([
            'type' => 'collection_banner',
            'title' => 'Rose Gold Collection',
            'sort_order' => 12,
            'is_active' => true,
        ]);

        $item = $block->items()->create([
            'itemable_type' => CollectionModel::class,
            'itemable_id' => $rose->id,
            'sort_order' => 0,
        ]);

        // Estele photography, stored locally in storage/app/seed-images —
        // distinct from the Collection's own square/tile image used in the "Shop by
        // Collection" grid, which is the wrong crop for a banner.
        try {
            $item->addMedia(storage_path('app/seed-images/homepage/rose-gold-collection-banner.png'))
                ->preservingOriginal()
                ->usingFileName('rose-gold-collection-banner.png')
                ->toMediaCollection('image');
        } catch (\Throwable $e) {
            $this->command?->warn("Could not fetch Rose Gold Collection banner image: {$e->getMessage()}");
        }
    }

    private function seedBanners(): void
    {
        if (Banner::count() > 0) {
            return;
        }

        // Estele photography, stored locally in storage/app/seed-images —
        // distinct from product/category photos, purpose-shot at 1800x700.
        $imagesDir = storage_path('app/seed-images/homepage');

        $slides = [
            ['slug' => 'grand-sale', 'title' => 'Grand Sale', 'image' => "{$imagesDir}/banner-grand-sale.png"],
            ['slug' => 'hasli', 'title' => 'Hasli Collection', 'image' => "{$imagesDir}/banner-hasli.png"],
            ['slug' => 'sitara', 'title' => 'Sitara Collection', 'image' => "{$imagesDir}/banner-sitara.png"],
            ['slug' => 'wedding-season', 'title' => 'Wedding Season', 'image' => "{$imagesDir}/banner-wedding-season.png"],
            ['slug' => 'maharani', 'title' => 'Maharani Collection', 'image' => "{$imagesDir}/banner-maharani.png"],
        ];

        foreach ($slides as $index => $slide) {
            $banner = Banner::create([
                'title' => $slide['title'],
                'link_url' => route('home'),
                'sort_order' => $index,
                'is_active' => true,
            ]);

            try {
                $banner->addMedia($slide['image'])
                    ->preservingOriginal()
                    ->usingFileName('banner-'.$slide['slug'].'.png')
                    ->toMediaCollection('image');
            } catch (\Throwable $e) {
                $this->command?->warn("Could not fetch banner image for {$slide['title']}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Create (or repair) a `product_carousel` block whose items are exactly the
     * products of the given collection, in pivot (insertion) order.
     *
     * Identifies the block by its sort_order slot rather than its title so the
     * title-less Trending row (sort_order 15) can also be matched. If the block
     * already holds the right product ids nothing changes; otherwise the items
     * are replaced — this is how an install seeded before CollectionSeeder ran
     * (Trending fell back to the featured products) heals itself on re-seed.
     */
    private function syncProductCarousel(?string $title, int $sortOrder, string $collectionSlug, ?string $ctaLabel = null): void
    {
        $collection = CollectionModel::where('slug', $collectionSlug)->first();
        if (! $collection) {
            return;
        }

        $block = HomepageBlock::where('type', 'product_carousel')
            ->where('sort_order', $sortOrder)
            ->first();

        $expectedIds = $collection->products()->orderBy('collection_product.id')->pluck('products.id');
        $currentIds = $block?->items()->orderBy('sort_order')->pluck('itemable_id')->all() ?? [];

        if ($expectedIds->diff($currentIds)->isEmpty() && count($currentIds) === $expectedIds->count()) {
            return;
        }

        if ($block) {
            $block->items()->delete();
        } else {
            $block = HomepageBlock::create([
                'type' => 'product_carousel',
                'title' => $title,
                'sort_order' => $sortOrder,
                'cta_label' => $ctaLabel,
                'cta_url' => $ctaLabel ? route('collections.show', $collection) : null,
                'is_active' => true,
            ]);
        }

        $collection->products()->orderBy('collection_product.id')->get()->each(function (Product $product, int $index) use ($block) {
            $block->items()->create([
                'itemable_type' => Product::class,
                'itemable_id' => $product->id,
                'sort_order' => $index,
            ]);
        });
    }

    private function seedNewArrivalsCarousel(): void
    {
        // Replaced by syncProductCarousel() — kept only so the old method name
        // doesn't break anyone calling it directly.
        $this->syncProductCarousel(title: 'New Arrivals', sortOrder: 25, collectionSlug: 'new-arrivals');
    }

    private function seedBestsellersCarousel(): void
    {
        // Replaced by syncProductCarousel().
        $this->syncProductCarousel(title: 'Bestsellers', sortOrder: 29, collectionSlug: 'best-seller', ctaLabel: 'View All');
    }

    private function seedPriceTiers(): void
    {
        if (HomepageBlock::where('type', 'price_tiers')->exists()) {
            return;
        }

        $block = HomepageBlock::create([
            'type' => 'price_tiers',
            'sort_order' => 27,
            'is_active' => true,
        ]);

        // Decorative price-range tiles from the original site — links are generic
        // (no real price-filtered collection query on the original either).
        $tierImagesDir = storage_path('app/seed-images/homepage');
        $tiers = [
            ['title' => 'Under', 'body' => '₹999', 'image' => "{$tierImagesDir}/price-tier-under.png"],
            ['title' => 'Under', 'body' => '₹1,499', 'image' => "{$tierImagesDir}/price-tier-under.png"],
            ['title' => 'Under', 'body' => '₹2,999', 'image' => "{$tierImagesDir}/price-tier-under.png"],
            ['title' => 'Premium', 'body' => 'Pearls', 'image' => "{$tierImagesDir}/price-tier-premium.png"],
        ];

        foreach ($tiers as $index => $tier) {
            $item = $block->items()->create([
                'title' => $tier['title'],
                'body' => $tier['body'],
                'link_url' => route('home'),
                'sort_order' => $index,
            ]);

            try {
                $item->addMedia($tier['image'])
                    ->preservingOriginal()
                    ->usingFileName('price-tier-'.$index.'.png')
                    ->toMediaCollection('image');
            } catch (\Throwable $e) {
                $this->command?->warn("Could not fetch price tier image: {$e->getMessage()}");
            }
        }
    }

    private function seedCelebrities(): void
    {
        if (HomepageBlock::where('type', 'celebrities')->exists()) {
            return;
        }

        $block = HomepageBlock::create([
            'type' => 'celebrities',
            'subtitle' => 'Glamour meets grace — worn by the stars, made for you.',
            'cta_label' => 'Shop Collection',
            'cta_url' => route('home'),
            'sort_order' => 30,
            'is_active' => true,
        ]);

        $celebImagesDir = storage_path('app/seed-images/homepage');

        $celebrities = [
            ['name' => 'Divyanka', 'image' => "{$celebImagesDir}/divyanka.webp"],
            ['name' => 'Jannat Zubair Rahmani', 'image' => "{$celebImagesDir}/jannat-zubair-rahmani.webp"],
            ['name' => 'Neeti Mohan', 'image' => "{$celebImagesDir}/neeti-mohan.webp"],
            ['name' => 'Yuvika Chaudhary', 'image' => "{$celebImagesDir}/yuvika-chaudhary.png"],
        ];

        foreach ($celebrities as $index => $celebrity) {
            $item = $block->items()->create([
                'title' => $celebrity['name'],
                'link_url' => route('home'),
                'sort_order' => $index,
            ]);

            try {
                $item->addMedia($celebrity['image'])
                    ->preservingOriginal()
                    ->usingFileName(basename($celebrity['image']))
                    ->toMediaCollection('image');
            } catch (\Throwable $e) {
                $this->command?->warn("Could not fetch celebrity image for {$celebrity['name']}: {$e->getMessage()}");
            }
        }
    }

    private function seedUsp(): void
    {
        if (HomepageBlock::where('type', 'usp')->exists()) {
            return;
        }

        $block = HomepageBlock::create([
            'type' => 'usp',
            'title' => null,
            'sort_order' => 40,
            'is_active' => true,
        ]);

        $items = [
            ['title' => '100% Anti-Tarnish', 'body' => 'Plating that stays bright, wear after wear.'],
            ['title' => '7-Day Return & Exchange', 'body' => 'Not the right fit? Send it back, hassle-free.'],
            ['title' => 'Free Shipping Available', 'body' => 'On eligible orders, delivered to your door.'],
        ];

        foreach ($items as $index => $item) {
            $block->items()->create([
                'title' => $item['title'],
                'body' => $item['body'],
                'sort_order' => $index,
            ]);
        }
    }

    private function seedTestimonials(): void
    {
        if (HomepageBlock::where('type', 'testimonials')->exists()) {
            return;
        }

        $block = HomepageBlock::create([
            'type' => 'testimonials',
            'title' => '5M+ Happy Customers',
            'sort_order' => 50,
            'is_active' => true,
        ]);

        $items = [
            ['title' => 'Samadi', 'body' => 'Soooooo comfortable. Soooooooo beautiful. Loved every bit of it.', 'rating' => 5],
            ['title' => 'V J', 'body' => 'Absolutely love the set. The back clip secures easily and gives good support.', 'rating' => 5],
            ['title' => 'Jyotsna', 'body' => "It's beautiful, quality is good, value for money. Shines like real gold.", 'rating' => 4],
            ['title' => 'Swapnanjali', 'body' => 'Very nice and cute product, gifted it to my sister for her birthday.', 'rating' => 5],
            ['title' => 'Charu', 'body' => 'Amazing product. Very beautiful earrings, loved so much.', 'rating' => 5],
        ];

        foreach ($items as $index => $item) {
            $block->items()->create([
                'title' => $item['title'],
                'body' => $item['body'],
                'rating' => $item['rating'],
                'sort_order' => $index,
            ]);
        }
    }

    private function seedBrandStory(): void
    {
        if (HomepageBlock::where('type', 'brand_story')->exists()) {
            return;
        }

        HomepageBlock::create([
            'type' => 'brand_story',
            'title' => 'Sparkle That Stays With You',
            'subtitle' => 'Handcrafted fashion jewellery, designed to last and made to be loved.',
            'sort_order' => 60,
            'is_active' => true,
        ]);
    }

    private function seedNewsletter(): void
    {
        if (HomepageBlock::where('type', 'newsletter')->exists()) {
            return;
        }

        HomepageBlock::create([
            'type' => 'newsletter',
            'title' => 'Get the Glow — Exclusive Access Awaits',
            'subtitle' => 'Subscribe to our emailer and get 5% off your first purchase',
            'sort_order' => 70,
            'is_active' => true,
        ]);
    }
}

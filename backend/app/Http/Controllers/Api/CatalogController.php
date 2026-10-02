<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponses;
use App\Http\Api\ProductResource;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController
{
    use ApiResponses;

    /**
     * GET /api/home — everything the home screen needs in one round trip.
     *
     * Mirrors the web homepage section-for-section. Every section is assembled
     * from real CMS rows (HomepageBlock + HomepageBlockItem, morphable to
     * Product / Category / Collection / Blog / Faq) and Settings, so nothing
     * is invented here. The legacy flat keys (`banners`, `blocks`, `offers`,
     * `products`) are kept so older app builds still render.
     */
    public function home(): JsonResponse
    {
        $blocks = \App\Models\HomepageBlock::active()
            ->ordered()
            ->with(['items.media', 'items.itemable' => fn ($morphTo) => $morphTo->morphWith([
                \App\Models\Blog::class => ['media', 'blogCategory'],
                Product::class => ['media', 'variants'],
                Category::class => ['media'],
                Collection::class => ['media'],
                \App\Models\Faq::class => [],
            ])])
            ->get();

        $byType = $blocks->groupBy('type');

        $categories = Category::orderBy('sort_order')->with('media')->get();

        $banners = \App\Models\Banner::active()->ordered()->with('media')->get();

        $offers = \App\Models\Offer::active()->ordered()->pluck('text')->values();

        // The web layout's announcement strip above the header always renders
        // `announcement_messages` (the individual offers strip is desktop-only,
        // `hidden md:block`). Mirror that exactly: the mobile promo bar must show
        // the same three CMS messages as the live site.
        $announcements = (array) json_decode(
            (string) \App\Models\Setting::where('key', 'announcement_messages')->value('value'),
            true
        );

        $promo = $announcements ?: [];

        $heroBanners = $banners->map(fn ($banner) => [
            'id' => $banner->id,
            'title' => $banner->title,
            'subtitle' => null,
            'image' => $banner->getFirstMediaUrl('image', 'desktop') ?: null,
            'mobile_image' => $banner->getMobileImageUrl(),
            'link_url' => $this->bannerLink($banner),
            'cta' => null,
        ])->values();

        $collectionBannerBlock = $byType->get('collection_banner')?->first() ?? null;

        if ($collectionBannerBlock) {
            $cbTitle = $collectionBannerBlock->title;
            $collectionBanners = $collectionBannerBlock->items->map(function ($item) use ($cbTitle) {
                $collection = $item->itemable instanceof Collection ? $item->itemable : null;

                return [
                    'id' => $item->id,
                    'title' => $cbTitle,
                    'subtitle' => $item->body,
                    'image' => $item->getFirstMediaUrl('image', 'banner')
                        ?: $item->getFirstMediaUrl('image', 'card')
                        ?: $collection?->getFirstMediaUrl('image', 'banner'),
                    'link_url' => $item->link_url,
                    'collection' => $collection ? $this->collectionRow($collection) : null,
                ];
            })->values();
        } else {
            $rose = Collection::where('slug', 'rose-collection')->first();
            $collectionBanners = $rose
                ? collect([[ // fallback banner mirrors the site's "Royal Rose Edition" section
                    'id' => null,
                    'title' => 'Rose Gold Collection',
                    'subtitle' => 'Our signature rose gold plating, in every category.',
                    'image' => $rose->getFirstMediaUrl('image', 'banner') ?: $rose->getFirstMediaUrl('image', 'tile'),
                    'link_url' => '/collections/rose-collection',
                    'collection' => $this->collectionRow($rose),
                ]])
                : collect();
        }

        $trending = $byType->get('product_carousel')
            ?->filter(fn ($b) => (int) $b->sort_order === 15)
            ->first();

        $newArrivals = $byType->get('product_carousel')
            ?->filter(fn ($b) => $b->title === 'New Arrivals')
            ->first();

        $bestsellers = $byType->get('product_carousel')
            ?->filter(fn ($b) => $b->title === 'Bestsellers')
            ->first();

        $collections = Collection::active()->ordered()->with('media')->withCount('products')->get();

        $priceTiers = ($byType->get('price_tiers')?->first() ?? null)
            ? $byType->get('price_tiers')->first()->items->map(function ($item) {
                $row = [
                    'id' => $item->id,
                    'label' => $item->title,
                    'amount' => $item->body,
                    'image' => $item->getFirstMediaUrl('image', 'card') ?: null,
                ];

                // Budget tiles drive a real price-filtered search — the range
                // is parsed from the CMS label + amount ("Under ₹499" → max
                // 499, "₹500–₹1,500" → both, "Premium ₹2,000+" → min 2000).
                return array_merge($row, $this->parsePriceTierRange((string) $item->title, (string) $item->body));
            })->values()
            : collect();

        $celebrities = ($byType->get('celebrities')?->first() ?? null)
            ? $byType->get('celebrities')->first()->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'image' => $item->getFirstMediaUrl('image', 'card') ?: null,
            ])->values()
            : collect();

        $benefits = ($byType->get('usp')?->first() ?? null)
            ? $byType->get('usp')->first()->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'body' => $item->body,
            ])->values()
            : collect();

        $testimonials = ($byType->get('testimonials')?->first() ?? null)
            ? $byType->get('testimonials')->first()->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'body' => $item->body,
                'rating' => $item->rating,
            ])->values()
            : collect();

        $journalBlock = $byType->get('journal')?->first() ?? null;

        $journal = $journalBlock ? [
            'title' => $journalBlock->title,
            'subtitle' => $journalBlock->subtitle,
            'cta_label' => $journalBlock->cta_label,
            'cta_url' => $journalBlock->cta_url,
            'items' => $journalBlock->items->map(function ($item) {
                if ($item->itemable instanceof \App\Models\Blog) {
                    $blog = $item->itemable;

                    return [
                        'id' => $item->id,
                        'type' => 'blog',
                        'title' => $blog->title,
                        'body' => $blog->excerpt,
                        'link_url' => '/blog/'.$blog->slug,
                        'image' => $blog->getFirstMediaUrl('featured_image', 'card') ?: null,
                        'author' => $blog->author_name,
                        'category' => $blog->blogCategory?->name,
                        'published_at' => $blog->published_at?->toIso8601String(),
                    ];
                }

                return [
                    'id' => $item->id,
                    'type' => 'promo',
                    'title' => $item->title,
                    'body' => $item->body,
                    'link_url' => $item->link_url,
                ];
            })->values(),
        ] : null;

        $instagramBlock = $byType->get('shop_the_look')?->first() ?? null;

        $instagram = $instagramBlock ? [
            'title' => $instagramBlock->title,
            'subtitle' => $instagramBlock->subtitle,
            'items' => $this->productCards($instagramBlock->items),
        ] : null;

        $statsBlock = $byType->get('brand_story')?->first() ?? null;

        $stats = $statsBlock ? [
            'title' => $statsBlock->title,
            'subtitle' => $statsBlock->subtitle,
            'cta_label' => $statsBlock->cta_label,
            'cta_url' => $statsBlock->cta_url,
            'items' => $statsBlock->items->map(fn ($item) => [
                'id' => $item->id,
                'value' => $item->title,
                'label' => $item->body,
            ])->values(),
        ] : null;

        $faqBlock = $byType->get('faq')?->first() ?? null;

        $faqs = $faqBlock ? $faqBlock->items->map(function ($item) {
            $faq = $item->itemable;

            return $faq instanceof \App\Models\Faq ? [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
            ] : null;
        })->filter()->values() : collect();

        $services = collect(json_decode((string) \App\Models\Setting::where('key', 'footer_usps')->value('value'), true) ?: [])
            ->map(fn ($s) => [
                'title' => $s['title'] ?? null,
                'body' => $s['body'] ?? null,
                'icon' => $s['icon'] ?? null,
            ])->values();

        $footer = collect(\App\Models\Setting::whereIn('key', [
            'footer_about', 'footer_company_name', 'footer_copyright',
            'contact_address', 'contact_hours', 'contact_email', 'contact_phone',
            'footer_popular_searches', 'footer_payment_badges',
        ])->pluck('value', 'key'))
            ->map(fn ($value) => is_string($value) && str_starts_with($value, '[') ? (json_decode($value, true) ?: $value) : $value)
            ->all();

        return $this->ok([
            // Legacy keys kept for older app builds.
            'categories' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image' => $category->getFirstMediaUrl('image', 'tile') ?: null,
                'product_count' => $category->products()->count(),
            ])->values(),
            'banners' => $heroBanners,
            'blocks' => $blocks->map(fn (\App\Models\HomepageBlock $block) => [
                'id' => $block->id,
                'type' => $block->type,
                'title' => $block->title,
                'subtitle' => $block->subtitle,
                'cta_label' => $block->cta_label,
                'cta_url' => $block->cta_url,
                'items' => $block->items->map(fn (\App\Models\HomepageBlockItem $item) => [
                    'id' => $item->id,
                    'title' => $item->title ?: $item->itemable?->title,
                    'subtitle' => $item->body,
                    'link_url' => $item->link_url,
                    'rating' => $item->rating,
                    'image' => $item->getFirstMediaUrl('image', 'card') ?: null,
                    'itemable_type' => $item->itemable ? class_basename($item->itemable) : null,
                    'product' => $item->itemable instanceof Product ? ProductResource::card($item->itemable) : null,
                ])->values(),
            ])->values(),
            'offers' => $offers,
            'products' => $this->productCards($blocks->flatMap->items),

            // Structured sections for the current app.
            'promo' => $promo,
            'hero_banners' => $heroBanners,
            'collection_banners' => $collectionBanners->values(),
            'trending_products' => $this->productCards($trending?->items ?? collect()),
            'trending_cta' => $trending?->cta_url ? $this->linkPath($trending->cta_url) : null,
            'trending_eyebrow' => $trending?->subtitle ?: 'Handpicked for you',
            'trending_title' => $trending?->title ?: 'Bestsellers',
            'collections' => $collections->map(fn ($collection) => $this->collectionRow($collection))->values(),
            'new_arrivals' => $this->productCards($newArrivals?->items ?? collect()),
            'new_arrivals_cta' => $newArrivals?->cta_url ? $this->linkPath($newArrivals->cta_url) : '/collections/new-arrivals',
            'new_arrivals_eyebrow' => $newArrivals?->subtitle ?: 'Handpicked for you',
            'new_arrivals_title' => $newArrivals?->title ?: 'New Arrivals',
            'bestsellers' => $this->productCards($bestsellers?->items ?? collect()),
            'bestsellers_cta' => $bestsellers?->cta_url ? $this->linkPath($bestsellers->cta_url) : '/collections/best-seller',
            'bestsellers_eyebrow' => $bestsellers?->subtitle ?: 'Handpicked for you',
            'bestsellers_title' => $bestsellers?->title ?: 'Bestsellers',
            'price_tiers' => $priceTiers->values(),
            'celebrities' => $celebrities->values(),
            'benefits' => $benefits->values(),
            'testimonials' => $testimonials->values(),
            'journal' => $journal,
            'instagram' => $instagram,
            'stats' => $stats,
            'faqs' => $faqs->values(),
            'services' => $services->values(),
            'footer' => $footer,
        ]);
    }

    private function collectionRow(Collection $collection): array
    {
        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'image' => $collection->getFirstMediaUrl('image', 'tile') ?: null,
            'product_count' => isset($collection->products_count) ? (int) $collection->products_count : (int) $collection->products()->count(),
        ];
    }

    private function productCards($items): array
    {
        return collect($items)
            ->filter(fn ($item) => $item->itemable instanceof Product)
            ->map(fn ($item) => ProductResource::card($item->itemable))
            ->values()
            ->all();
    }

    /**
     * Normalize a block CTA into a relative app href (`/collections/x`), so the
     * app never tries to open an absolute dev URL.
     */
    private function linkPath(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return '/' . ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');
    }

    /**
     * Home hero banners are navigation — a dead or host-only link (e.g. a stale
     * seed value like "http://10.0.140.206:8000") produces a no-op tap in the
     * app. Normalize to an internal path; if the row has no usable link,
     * guess the collection from the banner title, else fall back to the
     * best-seller collection so the tap always lands somewhere real.
     */
    private function bannerLink(\App\Models\Banner $banner): ?string
    {
        $link = trim((string) $banner->link_url);

        if ($link !== '') {
            $path = '/' . ltrim((string) parse_url($link, PHP_URL_PATH), '/');
            if ($path !== '/') {
                return $path;
            }
        }

        $slug = \Illuminate\Support\Str::slug(strip_tags((string) $banner->title));
        $guessed = '/collections/'.$slug;

        return \App\Models\Collection::where('slug', $slug)->exists()
            ? $guessed
            : '/collections/best-seller';
    }

    /**
     * Best-effort extraction of a price range from a budget-tier label +
     * amount (e.g. "Under ₹499" → max 499, "₹500–₹1,500" → min 500 max 1500,
     * "Premium ₹2,000+" → min 2000). The "Under"/"above" wording lives in the
     * CMS label column, the numbers in the amount. A lone number with no
     * direction word is treated as a cap (max) so a filtered search never
     * over-constrains to an exact match. Nulls mean "open-ended / unknown".
     */
    private function parsePriceTierRange(string $label, string $amount): array
    {
        $text = $label.' '.$amount;

        preg_match_all('/\d[\d,]*(?:\.\d+)?/', $amount, $matches);
        $numbers = array_map(fn ($n) => (float) str_replace(',', '', $n), $matches[0] ?? []);

        $min = null;
        $max = null;

        if (preg_match('/\bunder\b/i', $text) && isset($numbers[0])) {
            $max = $numbers[0];
        } elseif ((preg_match('/(above|over|\+)/i', $text) || str_contains($amount, '+')) && isset($numbers[0])) {
            $min = $numbers[0];
        } elseif (isset($numbers[0], $numbers[1])) {
            $min = $numbers[0];
            $max = max($numbers[0], $numbers[1]);
        } elseif (isset($numbers[0])) {
            $max = $numbers[0];
        }

        return ['min_price' => $min, 'max_price' => $max];
    }

    /**
     * GET /api/categories — all categories with counts + images.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::orderBy('sort_order')
            ->with('media', 'children')
            ->withCount('products')
            ->get();

        return $this->ok($categories->map(fn (Category $category) => [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->getFirstMediaUrl('image', 'tile') ?: null,
            'product_count' => $category->products_count,
            'parent_id' => $category->parent_id,
            'children' => $category->children->map(fn (Category $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
            ])->values(),
        ])->values());
    }

    /**
     * GET /api/categories/{slug}/products — paginated products in one category.
     */
    public function categoryProducts(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $query = Product::where('is_active', true)
            ->with('media', 'variants')
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id));

        $query = $this->applySort($query, $request);

        $products = $query->paginate($this->perPage($request))->withQueryString();

        return $this->paginated($products, fn (Product $p) => ProductResource::card($p));
    }

    /**
     * GET /api/collections — all active collections.
     */
    public function collections(): JsonResponse
    {
        $collections = Collection::active()
            ->ordered()
            ->with('media')
            ->withCount('products')
            ->get();

        return $this->ok($collections->map(fn (Collection $collection) => [
            'id' => $collection->id,
            'name' => $collection->name,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'image' => $collection->getFirstMediaUrl('image', 'tile') ?: null,
            'product_count' => $collection->products_count,
        ])->values());
    }

    /**
     * GET /api/collections/{slug}/products — paginated products in a collection.
     */
    public function collectionProducts(Request $request, string $slug): JsonResponse
    {
        $collection = Collection::active()->where('slug', $slug)->firstOrFail();

        $query = Product::where('is_active', true)
            ->with('media', 'variants')
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->whereHas('collections', fn ($q) => $q->where('collections.id', $collection->id));

        $query = $this->applySort($query, $request);

        $products = $query->paginate($this->perPage($request))->withQueryString();

        return $this->paginated($products, fn (Product $p) => ProductResource::card($p));
    }

    /**
     * GET /api/products/{slug} — full product detail incl. variants, gallery,
     * reviews, and related products.
     */
    public function productShow(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        abort_unless($product->is_active, 404);

        // Track the view (powers the "Trending" collection, seeded/top-viewed).
        \App\Models\ProductView::create(['product_id' => $product->id]);

        $product->load('variants', 'categories', 'collections', 'media');

        $related = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->with('media', 'variants')
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $product->categories->pluck('id')))
            ->take(8)
            ->get();

        $reviews = $product->approvedReviews()
            ->with('media')
            ->latest()
            ->paginate($this->perPage($request, 10), ['*'], 'reviews_page');

        return $this->ok([
            'product' => ProductResource::detail($product),
            'related_products' => $related->map(fn (Product $p) => ProductResource::card($p))->values(),
            'reviews' => [
                'data' => $reviews->items(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
            ],
            'rating_summary' => [
                'average' => $product->reviewsAverageRating(),
                'count' => $product->reviewsCount(),
            ],
        ]);
    }

    /**
     * GET /api/search — live + filtered search. Params: q, sort, min_price,
     * max_price, in_stock, category[], per_page.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $this->buildSearchQuery($request);

        // An empty search is only "no results" when there are no filters at
        // all — price-range browsing (budget tiles) sends min_price/max_price
        // without a keyword and must return real products.
        $hasQuery = trim((string) $request->query('q', '')) !== '';
        $hasFilters = $hasQuery
            || (string) $request->query('min_price', '') !== ''
            || (string) $request->query('max_price', '') !== ''
            || $request->boolean('in_stock')
            || array_filter((array) $request->query('category', [])) !== [];

        if (! $hasFilters) {
            return $this->paginated(new \Illuminate\Pagination\LengthAwarePaginator([], 0, 24), fn (Product $p) => $p);
        }

        $products = $query->paginate($this->perPage($request))->withQueryString();

        return $this->paginated($products, fn (Product $p) => ProductResource::card($p));
    }

    /**
     * GET /api/search/suggest — thin autocomplete, top 6 title/sku matches.
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return $this->ok([]);
        }

        // SQL LIKE fallback (MeiliSearch is a nice-to-have; the API must work
        // without its index — Scout gracefully falls back below).
        try {
            $builder = Product::search($query)->take(6);
            $results = $builder->get();
        } catch (\Throwable) {
            $results = Product::where('is_active', true)
                ->with('media')
                ->where(fn ($q) => $q->where('title', 'like', "%{$query}%")->orWhere('sku', 'like', "%{$query}%"))
                ->take(6)
                ->get();
        }

        return $this->ok($results->map(fn (Product $product) => [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'price' => (float) $product->price,
            'thumbnail' => $product->getFirstMediaUrl('gallery', 'card') ?: null,
        ])->values());
    }

    /* ---------------------------------------------------------------- */

    private function applySort($query, Request $request)
    {
        return match ($request->query('sort', 'relevance')) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    private function buildSearchQuery(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $inStock = $request->boolean('in_stock');
        $categorySlugs = array_values(array_filter((array) $request->query('category', [])));

        $builder = Product::where('is_active', true)
            ->with('media', 'variants')
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating');

        if ($query !== '') {
            $builder->where(fn ($q) => $q->where('title', 'like', "%{$query}%")->orWhere('sku', 'like', "%{$query}%"));
        }

        if ($minPrice !== null && $minPrice !== '') {
            $builder->where('price', '>=', (float) $minPrice);
        }
        if ($maxPrice !== null && $maxPrice !== '') {
            $builder->where('price', '<=', (float) $maxPrice);
        }

        if ($categorySlugs !== []) {
            $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id');
            $builder->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
        }

        if ($inStock) {
            $builder->where(fn ($q) => $q
                ->whereHas('variants', fn ($v) => $v->where('stock_quantity', '>', 0))
                ->orWhere(fn ($p) => $p->whereDoesntHave('variants')->where('stock_quantity', '>', 0)));
        }

        return $this->applySort($builder, $request);
    }
}
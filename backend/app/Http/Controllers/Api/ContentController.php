<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponses;
use App\Http\Api\ProductResource;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController
{
    use ApiResponses;

    /**
     * GET /api/blogs — paginated published posts + categories.
     */
    public function blogs(Request $request): JsonResponse
    {
        $categorySlug = $request->query('category');

        $query = Blog::published()->with('blogCategory', 'media')->latest('published_at');

        if ($categorySlug) {
            $query->whereHas('blogCategory', fn ($q) => $q->where('slug', $categorySlug));
        }

        $posts = $query->paginate($this->perPage($request, 12));

        $categories = BlogCategory::orderBy('sort_order')->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
        ])->values();

        return $this->paginated($posts, fn (Blog $post) => $this->blogPayload($post, false), [
            'categories' => $categories,
        ]);
    }

    /**
     * GET /api/blogs/{slug} — one published post with full body.
     */
    public function blogShow(string $slug): JsonResponse
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();

        abort_unless($blog->status === 'published' && $blog->published_at?->isPast(), 404);

        $blog->loadMissing('blogCategory', 'media');

        $related = Blog::published()
            ->where('id', '!=', $blog->id)
            ->when($blog->blog_category_id, fn ($q) => $q->where('blog_category_id', $blog->blog_category_id))
            ->take(4)
            ->get();

        return $this->ok([
            'post' => $this->blogPayload($blog, true),
            'related_posts' => $related->map(fn (Blog $b) => $this->blogPayload($b, false))->values(),
        ]);
    }

    /**
     * GET /api/faq — FAQ categories with their questions folded in.
     */
    public function faq(): JsonResponse
    {
        $categories = FaqCategory::with(['faqs' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (FaqCategory $category) => $category->faqs->isNotEmpty())
            ->map(fn (FaqCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'faqs' => $category->faqs->map(fn (Faq $faq) => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                ])->values(),
            ])
            ->values();

        return $this->ok($categories);
    }

    /**
     * GET /api/pages/{slug} — a published CMS page.
     */
    public function page(string $slug): JsonResponse
    {
        $page = CmsPage::where('slug', $slug)->firstOrFail();

        abort_unless($page->status === 'published', 404);

        return $this->ok([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
        ]);
    }

    /**
     * GET /api/stores — static store locator list for Store Locator page.
     * (Grows from admins later; static for now, consistent with the web sitemap.)
     */
    public function stores(): JsonResponse
    {
        return $this->ok([
            [
                'name' => 'Estele — Bandra Flagship',
                'address' => 'Ground Floor, 14 Linking Road, Bandra West',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'postal_code' => '400050',
                'phone' => '+91 98200 12345',
                'hours' => '11:00 AM – 9:00 PM',
            ],
            [
                'name' => 'Estele — Connaught Place',
                'address' => 'Shop 7, Block C, Outer Circle',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'postal_code' => '110001',
                'phone' => '+91 98110 54321',
                'hours' => '11:00 AM – 8:30 PM',
            ],
            [
                'name' => 'Estele — Brigade Road',
                'address' => '74 Brigade Road, Shanthala Nagar',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560025',
                'phone' => '+91 98800 98765',
                'hours' => '10:00 AM – 9:00 PM',
            ],
        ]);
    }

    /**
     * POST /api/products/{slug}/reviews — submit a review (starts pending).
     *
     * Requires a bearer token: the review is attributed to the authenticated
     * user (no more spoofable customer_email/header), deduplicated per
     * user+product, and marked `is_verified_purchase` only when that user has
     * a delivered (non-cancelled) order containing the product.
     */
    public function storeReview(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $user = $request->user();
        if (! $user) {
            return $this->error('Please log in to submit a review.', 401);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $alreadyReviewed = Review::where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyReviewed) {
            return $this->error('You have already reviewed this product.', 422, [
                'rating' => ['You have already reviewed this product.'],
            ]);
        }

        $matchingOrder = $user->orders()
            ->where('status', '!=', 'cancelled')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest()
            ->first();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_id' => $matchingOrder?->id,
            'customer_name' => $user->name ?: (string) $user->phone,
            'customer_email' => $user->email,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'status' => 'pending',
            'is_verified_purchase' => $matchingOrder !== null,
        ]);

        return $this->created([
            'id' => $review->id,
            'message' => 'Thanks for your review! It will appear once approved.',
        ]);
    }

    /**
     * POST /api/newsletter/subscribe.
     */
    public function newsletterSubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        NewsletterSubscriber::firstOrCreate(
            ['email' => $validated['email']],
            ['source' => 'app'],
        );

        return $this->message('Subscribed. Thank you!');
    }

    /**
     * GET /api/wishlist?ids[]=1&ids[]=2 — returns full product cards for a set
     * of saved product ids. The wishlist itself lives on the device (like the
     * web app's localStorage); this just hydrates the client's id list.
     */
    public function wishlist(Request $request): JsonResponse
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->query('ids', []))));

        if ($ids === []) {
            return $this->ok([]);
        }

        $products = Product::where('is_active', true)
            ->with('media', 'variants')
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id, '.implode(',', $ids).')')
            ->get();

        return $this->ok($products->map(fn (Product $p) => ProductResource::card($p))->values());
    }

    private function blogPayload(Blog $blog, bool $fullBody): array
    {
        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt,
            'content' => $fullBody ? $blog->content : null,
            'author' => $blog->author_name,
            'category' => $blog->blogCategory?->name ?? null,
            'category_slug' => $blog->blogCategory?->slug ?? null,
            'published_at' => $blog->published_at?->toIso8601String(),
            'is_featured' => $blog->is_featured,
            'image' => $blog->getFirstMediaUrl('featured_image', 'card') ?: null,
            'detail_image' => $blog->getFirstMediaUrl('featured_image', 'detail') ?: null,
        ];
    }
}
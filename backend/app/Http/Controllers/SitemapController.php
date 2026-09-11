<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Collection as CollectionModel;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Dynamic XML sitemap — regenerates as products/categories/etc. are
     * added, per the SEO checklist's "set this up as part of the build, not
     * manually later." Cached briefly since building it queries every
     * indexable table; short TTL keeps new products showing up quickly
     * without hitting the DB on every crawler request.
     */
    public function index(): Response
    {
        $urls = Cache::remember('sitemap.urls', 3600, function () {
            $urls = [];

            $urls[] = ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'];
            $urls[] = ['loc' => route('categories.index'), 'priority' => '0.7', 'changefreq' => 'weekly'];
            $urls[] = ['loc' => route('collections.index'), 'priority' => '0.7', 'changefreq' => 'weekly'];
            $urls[] = ['loc' => route('search'), 'priority' => '0.3', 'changefreq' => 'monthly'];
            $urls[] = ['loc' => route('blogs.index'), 'priority' => '0.6', 'changefreq' => 'weekly'];
            $urls[] = ['loc' => route('faq.index'), 'priority' => '0.4', 'changefreq' => 'monthly'];

            foreach (Category::orderBy('sort_order')->get(['slug', 'updated_at']) as $category) {
                $urls[] = [
                    'loc' => route('categories.show', $category->slug),
                    'lastmod' => $category->updated_at?->toAtomString(),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                ];
            }

            foreach (CollectionModel::active()->get(['slug', 'updated_at']) as $collection) {
                $urls[] = [
                    'loc' => route('collections.show', $collection->slug),
                    'lastmod' => $collection->updated_at?->toAtomString(),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                ];
            }

            foreach (Product::where('is_active', true)->select(['slug', 'updated_at'])->cursor() as $product) {
                $urls[] = [
                    'loc' => route('products.show', $product->slug),
                    'lastmod' => $product->updated_at?->toAtomString(),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                ];
            }

            foreach (Blog::published()->get(['slug', 'updated_at']) as $blog) {
                $urls[] = [
                    'loc' => route('blogs.show', $blog->slug),
                    'lastmod' => $blog->updated_at?->toAtomString(),
                    'priority' => '0.5',
                    'changefreq' => 'monthly',
                ];
            }

            foreach (CmsPage::query()->get(['slug', 'updated_at']) as $page) {
                $urls[] = [
                    'loc' => route('pages.show', $page->slug),
                    'lastmod' => $page->updated_at?->toAtomString(),
                    'priority' => '0.4',
                    'changefreq' => 'monthly',
                ];
            }

            return $urls;
        });

        return response()->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}

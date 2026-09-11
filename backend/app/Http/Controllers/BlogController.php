<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $categorySlug = $request->query('category');
        $page = $request->query('page', 1);
        $cacheKey = "blog.index.{$categorySlug}.{$page}";

        $posts = Cache::tags(['blog'])->remember($cacheKey, now()->addMinutes(15), function () use ($categorySlug) {
            $query = Blog::published()->with('blogCategory')->latest('published_at');

            if ($categorySlug) {
                $query->whereHas('blogCategory', fn ($q) => $q->where('slug', $categorySlug));
            }

            return $query->paginate(12)->withQueryString();
        });

        $categories = Cache::tags(['blog'])->remember('blog.categories', now()->addHour(), function () {
            return BlogCategory::orderBy('sort_order')->get();
        });

        return view('blog.index', compact('posts', 'categories', 'categorySlug'));
    }

    public function show(Blog $blog)
    {
        abort_unless($blog->status === 'published' && $blog->published_at?->isPast(), 404);

        $blog->loadMissing('blogCategory');

        $relatedPosts = Cache::tags(['blog'])->remember("blog.{$blog->id}.related", now()->addMinutes(15), function () use ($blog) {
            if (! $blog->blog_category_id) {
                return collect();
            }

            return Blog::published()
                ->where('id', '!=', $blog->id)
                ->where('blog_category_id', $blog->blog_category_id)
                ->take(4)
                ->get();
        });

        return view('blog.show', compact('blog', 'relatedPosts'));
    }
}

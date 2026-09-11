<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blog::scopePublished() and the show()/index() abort_unless gates are what
 * keep drafts and future-scheduled posts off the public storefront — this
 * locks that down plus the category filter on the index route.
 */
class VerifyBlogPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_post_returns_404(): void
    {
        $blog = Blog::create([
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'content' => '<p>Draft</p>',
            'status' => 'draft',
        ]);

        $this->get(route('blogs.show', $blog))->assertNotFound();
    }

    public function test_future_scheduled_post_returns_404(): void
    {
        $blog = Blog::create([
            'title' => 'Future Post',
            'slug' => 'future-post',
            'content' => '<p>Future</p>',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('blogs.show', $blog))->assertNotFound();
    }

    public function test_published_post_is_visible(): void
    {
        $blog = Blog::create([
            'title' => 'Published Post',
            'slug' => 'published-post',
            'content' => '<p>Hello world</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('blogs.show', $blog))
            ->assertOk()
            ->assertSee('Published Post');
    }

    public function test_index_filters_by_category(): void
    {
        $categoryA = BlogCategory::create(['name' => 'Style', 'slug' => 'style', 'sort_order' => 1]);
        $categoryB = BlogCategory::create(['name' => 'Care', 'slug' => 'care', 'sort_order' => 2]);

        Blog::create([
            'blog_category_id' => $categoryA->id,
            'title' => 'Style Post',
            'slug' => 'style-post',
            'content' => '<p>Style</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        Blog::create([
            'blog_category_id' => $categoryB->id,
            'title' => 'Care Post',
            'slug' => 'care-post',
            'content' => '<p>Care</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blogs.index', ['category' => 'style']));

        $response->assertOk();
        $response->assertSee('Style Post');
        $response->assertDontSee('Care Post');
    }
}

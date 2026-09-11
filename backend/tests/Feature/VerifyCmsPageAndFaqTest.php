<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CmsPageController@show gates on status the same way BlogController does,
 * and FaqController@index must only surface categories that actually have
 * FAQs (an empty category would otherwise render an empty heading).
 */
class VerifyCmsPageAndFaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_cms_page_returns_404(): void
    {
        $page = CmsPage::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'content' => '<p>Draft</p>',
            'status' => 'draft',
        ]);

        $this->get(route('pages.show', $page))->assertNotFound();
    }

    public function test_published_cms_page_is_visible(): void
    {
        $page = CmsPage::create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => '<p>Our story</p>',
            'status' => 'published',
        ]);

        $this->get(route('pages.show', $page))
            ->assertOk()
            ->assertSee('About Us');
    }

    public function test_faq_index_shows_questions_grouped_by_category(): void
    {
        $category = FaqCategory::create(['name' => 'Shipping', 'sort_order' => 1]);
        Faq::create([
            'faq_category_id' => $category->id,
            'question' => 'How long does delivery take?',
            'answer' => '3-7 business days.',
            'sort_order' => 1,
        ]);

        $emptyCategory = FaqCategory::create(['name' => 'Empty', 'sort_order' => 2]);

        $response = $this->get(route('faq.index'));

        $response->assertOk();
        $response->assertSee('How long does delivery take?');
        $response->assertDontSee('Empty');
    }
}

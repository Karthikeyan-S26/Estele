<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\Popup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Popup::scopeEligible() gates on is_active + the schedule window; whether a
 * *specific* visitor actually sees an eligible popup additionally depends on
 * the target_new_visitors_only flag checked against the 'estele_visited'
 * cookie in SiteDataComposer — the two are tested separately below.
 */
class VerifyPopupsAndNewsletterTest extends TestCase
{
    use RefreshDatabase;

    private function makePopup(array $overrides = []): Popup
    {
        return Popup::create(array_merge([
            'name' => 'Test Popup',
            'type' => 'newsletter',
            'trigger' => 'delay',
            'delay_seconds' => 4,
            'title' => 'Get the Glow',
            'body' => '5% off your first purchase.',
            'show_email_field' => true,
            'is_active' => true,
            'target_new_visitors_only' => false,
            'sort_order' => 0,
        ], $overrides));
    }

    public function test_inactive_popup_is_not_eligible(): void
    {
        $this->makePopup(['is_active' => false]);

        $this->assertSame(0, Popup::eligible()->count());
    }

    public function test_popup_outside_schedule_window_is_not_eligible(): void
    {
        $this->makePopup(['starts_at' => now()->addDay()]);
        $this->makePopup(['name' => 'Expired', 'ends_at' => now()->subDay()]);

        $this->assertSame(0, Popup::eligible()->count());
    }

    public function test_popup_within_schedule_window_is_eligible(): void
    {
        $this->makePopup(['starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);

        $this->assertSame(1, Popup::eligible()->count());
    }

    public function test_popup_renders_on_homepage_for_a_first_time_visitor(): void
    {
        $this->makePopup(['title' => 'Get the Glow – Exclusive Access Awaits']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Get the Glow – Exclusive Access Awaits');
        $response->assertCookie('estele_visited');
    }

    public function test_new_visitor_only_popup_is_hidden_from_returning_visitors(): void
    {
        $this->makePopup(['title' => 'New Visitor Only Popup', 'target_new_visitors_only' => true]);

        $response = $this->withCookie('estele_visited', '1')->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('New Visitor Only Popup');
    }

    public function test_untargeted_popup_still_shows_to_returning_visitors(): void
    {
        $this->makePopup(['title' => 'Shown To Everyone', 'target_new_visitors_only' => false]);

        $response = $this->withCookie('estele_visited', '1')->get(route('home'));

        $response->assertOk();
        $response->assertSee('Shown To Everyone');
    }

    /**
     * Regression test for a real bug caught in mentor-style self-review: the
     * composer used to fetch only the single highest-priority eligible popup
     * and return null if targeting excluded it, even when a second, untargeted
     * popup was eligible and should have shown instead.
     */
    public function test_returning_visitor_falls_back_to_a_lower_priority_untargeted_popup(): void
    {
        $this->makePopup([
            'name' => 'Top priority, new visitors only',
            'title' => 'New Visitor Exclusive',
            'target_new_visitors_only' => true,
            'sort_order' => 0,
        ]);
        $this->makePopup([
            'name' => 'Lower priority, everyone',
            'title' => 'Everyone Gets This One',
            'target_new_visitors_only' => false,
            'sort_order' => 1,
        ]);

        $response = $this->withCookie('estele_visited', '1')->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('New Visitor Exclusive');
        $response->assertSee('Everyone Gets This One');
    }

    public function test_newsletter_subscription_is_recorded(): void
    {
        $popup = $this->makePopup();

        $this->post(route('newsletter.subscribe'), [
            'email' => 'jane@example.com',
            'popup_id' => $popup->id,
        ])->assertRedirect();

        $subscriber = NewsletterSubscriber::first();
        $this->assertNotNull($subscriber);
        $this->assertSame('jane@example.com', $subscriber->email);
        $this->assertSame('popup', $subscriber->source);
        $this->assertSame($popup->id, $subscriber->popup_id);
    }

    public function test_duplicate_subscription_does_not_create_a_second_row(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'jane@example.com']);
        $this->post(route('newsletter.subscribe'), ['email' => 'jane@example.com']);

        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, NewsletterSubscriber::count());
    }
}

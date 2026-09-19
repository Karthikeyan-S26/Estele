<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Account > Profile Details. Name and email are editable; phone is not,
 * because the OTP login resolves an account by it — accepting a new number
 * here, unverified, would lock the customer out of their own account.
 */
class AccountProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_name_and_email(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $this->actingAs($user)
            ->patch(route('account.profile'), ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertRedirect(route('account.index'));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function test_phone_is_never_changed_by_this_form(): void
    {
        $user = User::factory()->create(['phone' => '9833011764']);

        $this->actingAs($user)
            ->patch(route('account.profile'), [
                'name' => $user->name,
                'phone' => '9000000000',
            ])
            ->assertRedirect(route('account.index'));

        $this->assertSame('9833011764', $user->refresh()->phone);
    }

    public function test_email_already_taken_by_another_user_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($user)
            ->patch(route('account.profile'), ['name' => $user->name, 'email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertSame('mine@example.com', $user->refresh()->email);
    }

    public function test_keeping_your_own_email_is_not_a_duplicate(): void
    {
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($user)
            ->patch(route('account.profile'), ['name' => 'Renamed', 'email' => 'mine@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $user->refresh()->name);
    }

    public function test_profile_panel_is_closed_until_it_is_opened(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('account.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<details[^>]*id="profile-details"(?![^>]*\sopen)[^>]*>/',
            $html,
            'Profile Details should render collapsed on a normal page load.'
        );
    }

    public function test_profile_panel_reopens_when_validation_failed(): void
    {
        // A rejected save redirects back with the validator's bag flashed to
        // the session; the view reads it as $errors, which is what the
        // panel's `open` attribute keys off. Rendering the view directly
        // with that bag shared exercises the same Blade condition the real
        // request hits, without fighting the test client over session
        // continuity across two separate calls.
        \Illuminate\Support\Facades\View::share('siteSettings', ['site_name' => 'Estele']);
        \Illuminate\Support\Facades\View::share('errors', (new \Illuminate\Support\ViewErrorBag())->put(
            'default',
            new \Illuminate\Support\MessageBag(['name' => ['The name field is required.']])
        ));

        $user = User::factory()->create();
        $this->actingAs($user);

        $html = view('account.index', [
            'orders' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1, ['path' => '/']),
            'statusCounts' => [],
            'walletBalance' => 0,
        ])->render();

        $this->assertMatchesRegularExpression(
            '/<details[^>]*id="profile-details"[^>]*\sopen/',
            $html,
            'A rejected save must come back with the form visible.'
        );
    }
}

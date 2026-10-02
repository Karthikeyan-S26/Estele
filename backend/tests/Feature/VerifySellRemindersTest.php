<?php

namespace Tests\Feature;

use App\Mail\WalletExpiryReminder;
use App\Models\SellRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Carbon\CarbonImmutable;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * The sell:send-reminders ladder — 3-day heads-up, 1-day nudge, post-expiry
 * notice — plus its dedupe columns and blank-email safety.
 */
class VerifySellRemindersTest extends TestCase
{
    use RefreshDatabase;
    use SellTestHelpers;

    private CarbonImmutable $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        Mail::fake();
        $this->base = CarbonImmutable::parse('2026-09-01 10:00:00');
        $this->travelTo($this->base);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function makeExpiringCredit(string $reason = 'reminder_test'): int
    {
        $customer = $this->makeCustomer();
        $tx = app(WalletService::class)->credit(
            $customer, 4500, $reason, null, $this->base->addDays(10),
        );

        return $tx->id;
    }

    public function test_the_three_day_reminder_is_sent_and_deduped(): void
    {
        $txId = $this->makeExpiringCredit();

        $this->travelTo($this->base->addDays(7)); // exactly 3 days left
        $this->artisan('sell:send-reminders')->assertSuccessful();

        $tx = WalletTransaction::findOrFail($txId);
        $this->assertNotNull($tx->reminder_3d_sent_at);
        $this->assertNull($tx->reminder_1d_sent_at);
        $this->assertNull($tx->expiry_notified_at);
        Mail::assertQueued(WalletExpiryReminder::class, 1);

        // Re-running the command must not send the same reminder twice.
        $this->artisan('sell:send-reminders')->assertSuccessful();
        Mail::assertQueued(WalletExpiryReminder::class, 1);
        $this->assertNotNull(WalletTransaction::findOrFail($txId)->reminder_3d_sent_at);
    }

    public function test_the_one_day_reminder_fires_after_the_three_day_one(): void
    {
        $txId = $this->makeExpiringCredit();

        $this->travelTo($this->base->addDays(7));
        $this->artisan('sell:send-reminders')->assertSuccessful();

        $this->travelTo($this->base->addDays(9)); // exactly 1 day left
        $this->artisan('sell:send-reminders')->assertSuccessful();

        $tx = WalletTransaction::findOrFail($txId);
        $this->assertNotNull($tx->reminder_3d_sent_at);
        $this->assertNotNull($tx->reminder_1d_sent_at);
        Mail::assertQueued(WalletExpiryReminder::class, 2);
    }

    public function test_the_post_expiry_notice_is_sent_after_the_sweep(): void
    {
        $txId = $this->makeExpiringCredit();

        $this->travelTo($this->base->addDays(10));
        $this->artisan('sell:wallet-expire')->assertSuccessful();
        $this->artisan('sell:send-reminders')->assertSuccessful();

        $tx = WalletTransaction::findOrFail($txId);
        $this->assertNotNull($tx->expired_at);
        $this->assertNotNull($tx->expiry_notified_at);
        Mail::assertQueued(WalletExpiryReminder::class, 1);

        // Swept + notified credits are never re-noticed.
        $this->artisan('sell:send-reminders')->assertSuccessful();
        Mail::assertQueued(WalletExpiryReminder::class, 1);
    }

    public function test_the_reminders_are_scoped_to_real_expiring_credits_only(): void
    {
        $customer = $this->makeCustomer();
        // A permanent credit (no expiry) must never be reminded.
        app(WalletService::class)->credit($customer, 1000, 'reward', null, null);

// A permanent credit (no expiry) must never be reminded — the WalletCredited
        // notification queued by WalletService is unrelated and expected.
        $this->travelTo($this->base->addDays(7));
        $this->artisan('sell:send-reminders')->assertSuccessful();

        Mail::assertQueued(WalletExpiryReminder::class, 0);
    }

    public function test_credits_of_phone_only_users_are_skipped_without_error(): void
    {
        $customer = $this->makeCustomer(['email' => null, 'phone' => '9'.str_repeat('1', 9)]);
        $tx = app(WalletService::class)->credit(
            $customer, 4500, 'reminder_blank_email', null, $this->base->addDays(10),
        );
        app(WalletService::class)->debit($customer, 1000, 'order_purchase');

        $this->travelTo($this->base->addDays(9));
        $this->artisan('sell:send-reminders')->assertSuccessful();

$this->assertNull($tx->fresh()->reminder_3d_sent_at);
        $this->assertNull($tx->fresh()->reminder_1d_sent_at);
        Mail::assertQueued(WalletExpiryReminder::class, 0);
    }
}

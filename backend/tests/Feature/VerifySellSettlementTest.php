<?php

namespace Tests\Feature;

use App\Mail\WalletCredited;
use App\Models\SellAuditLog;
use App\Models\SellRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * Settlement maths and guards: 90%/10% split off the admin valuation, the
 * +10-day wallet credit window, idempotent/guarded state transitions and the
 * phone-only-customer edge (regression for the WalletService email bug).
 */
class VerifySellSettlementTest extends TestCase
{
    use RefreshDatabase;
    use SellTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        Mail::fake();
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function valuationReviewSell(User $customer, User $vendor, float $bid = 8000): SellRequest
    {
        $sell = $this->openedSellRequest($customer, $vendor, $bid);
        $this->sellService()->closeBidding($sell);

        return $sell->fresh();
    }

    public function test_settlement_credits_ninety_percent_and_deducts_ten(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->valuationReviewSell($customer, $vendor);

        $settled = $this->sellService()->settle($sell, 10000);

        $this->assertTrue($settled);
        $sell = $sell->fresh();
        $this->assertSame(SellRequest::STATUS_COMPLETED, $sell->status);
        $this->assertSame('10000.00', $sell->admin_valuation);
        $this->assertSame('1000.00', $sell->deduction_amount);
        $this->assertSame('9000.00', $sell->wallet_credit);
        $this->assertTrue($sell->isSettled());
        $this->assertNotNull($sell->wallet_transaction_id);

        $this->assertSame('9000.00', $customer->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $customer->id,
            'type' => 'credit',
            'amount' => '9000.00',
            'reason' => WalletTransaction::REASON_SELL_SETTLEMENT,
        ]);
    }

    public function test_the_wallet_credit_is_valid_for_ten_days_from_settlement(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->valuationReviewSell($customer, $vendor);

        $this->sellService()->settle($sell, 10000);

        $tx = WalletTransaction::where('reason', WalletTransaction::REASON_SELL_SETTLEMENT)->firstOrFail();
        $this->assertNotNull($tx->expires_at);
        $expected = $sell->fresh()->settlement_at->copy()->addDays(SellRequest::WALLET_CREDIT_VALID_DAYS);
        $this->assertTrue(abs($tx->expires_at->diffInSeconds($expected)) < 5);
        $this->assertTrue($tx->isExpiring());
        $this->assertSame('expiring', $tx->lifetimeStatus());
    }

    public function test_the_customer_is_emailed_the_credit_note(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->valuationReviewSell($customer, $vendor);

        $this->sellService()->settle($sell, 10000);

        Mail::assertQueued(WalletCredited::class, fn (WalletCredited $mail) => $mail->hasTo($customer->email));
    }

    public function test_decimals_are_rounded_to_two_places(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->valuationReviewSell($customer, $vendor);

        $this->sellService()->settle($sell, 3333.33);

        $sell = $sell->fresh();
        $this->assertSame('3333.33', $sell->admin_valuation);
        $this->assertSame('333.33', $sell->deduction_amount);
        $this->assertSame('3000.00', $sell->wallet_credit);
        $this->assertSame('3000.00', $customer->fresh()->wallet_balance);
    }

    public function test_settlement_is_idempotent_and_never_double_credits(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->valuationReviewSell($customer, $vendor);

        $this->assertTrue($this->sellService()->settle($sell, 10000));
        $this->assertFalse($this->sellService()->settle($sell->fresh(), 20000));

        $this->assertSame('9000.00', $customer->fresh()->wallet_balance);
        $this->assertSame(1, WalletTransaction::count());
        $this->assertSame(1, SellAuditLog::where('event', 'settlement_completed')->count());
        $this->assertSame('10000.00', $sell->fresh()->admin_valuation);
    }

    public function test_settlement_refuses_a_request_not_in_valuation_review(): void
    {
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer); // pending_bids

        $this->assertFalse($this->sellService()->settle($sell, 10000));
        $this->assertSame(SellRequest::STATUS_PENDING_BIDS, $sell->fresh()->status);
        $this->assertSame('0.00', $customer->fresh()->wallet_balance);
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_settlement_refuses_an_expired_request(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->openedSellRequest($customer, $vendor); // no bids

        $this->travelTo($sell->bids_end_at->copy()->addSecond());
        $this->artisan('sell:bids-close')->assertSuccessful();
        $this->assertSame(SellRequest::STATUS_EXPIRED, $sell->fresh()->status);

        $this->assertFalse($this->sellService()->settle($sell->fresh(), 10000));
        $this->assertSame(0, WalletTransaction::count());
        $this->assertSame('0.00', $customer->fresh()->wallet_balance);
    }

    public function test_settlement_is_recorded_in_the_audit_trail(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->valuationReviewSell($customer, $vendor);

        $this->sellService()->settle($sell, 10000.00);

        $audit = SellAuditLog::where('event', 'settlement_completed')->firstOrFail();
        $this->assertSame(SellRequest::STATUS_VALUATION_REVIEW, $audit->from_status);
        $this->assertSame(SellRequest::STATUS_COMPLETED, $audit->to_status);
        $this->assertSame(10000, $audit->metadata['valuation']);
        $this->assertSame(1000, $audit->metadata['deduction_amount']);
        $this->assertSame(9000, $audit->metadata['wallet_credit']);
        $this->assertStringContainsString('T', $audit->metadata['wallet_expires_at']);
    }

    /**
     * A customer registered by phone alone has no email. Settlement must still
     * credit the wallet and complete the request — Mail::to(null) normalises to
     * an empty recipient list (never throws), so the money flow is unaffected.
     * This guards the phone-only path so a future change to the mailer cannot
     * silently break settlement for such customers.
     *
     * Deliberately does NOT use Mail::fake() so the real mail chain runs (into
     * the test array transport) exactly as production would.
     */
    public function test_settlement_survives_for_a_phone_only_customer_without_an_email(): void
    {
        $customer = $this->makeCustomer(['email' => null]);
        $vendor = $this->makeVendor();
        $sell = $this->valuationReviewSell($customer, $vendor);

        $settled = $this->sellService()->settle($sell->fresh(), 5000);

        $this->assertTrue($settled);
        $this->assertSame(SellRequest::STATUS_COMPLETED, $sell->fresh()->status);
        $this->assertSame('4500.00', $customer->fresh()->wallet_balance);
        $this->assertSame(1, WalletTransaction::count());
        $this->assertTrue($sell->fresh()->isSettled());
    }
}
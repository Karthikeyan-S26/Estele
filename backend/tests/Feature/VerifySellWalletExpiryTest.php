<?php

namespace Tests\Feature;

use App\Models\SellRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * The sell:wallet-expire sweep: crisp 10-day boundary, idempotent single
 * strike, and partial-use behaviour — whatever remains of the credit when the
 * window passes is struck off the balance.
 */
class VerifySellWalletExpiryTest extends TestCase
{
    use RefreshDatabase;
    use SellTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        Mail::fake();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function expiringCredit(User $user, float $amount, int $validDays = SellRequest::WALLET_CREDIT_VALID_DAYS): WalletTransaction
    {
        return app(WalletService::class)->credit(
            $user,
            $amount,
            WalletTransaction::REASON_SELL_SETTLEMENT,
            null,
            now()->addDays($validDays),
        );
    }

    public function test_a_credit_is_swept_exactly_at_its_expiry_boundary(): void
    {
        $customer = $this->makeCustomer();
        $credit = $this->expiringCredit($customer, 9000);

        $this->travelTo(now()->addDays(10)); // expires_at <= now
        $this->artisan('sell:wallet-expire')->assertSuccessful();

        $this->assertSame('0.00', $customer->fresh()->wallet_balance);
        $this->assertNotNull($credit->fresh()->expired_at);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $customer->id,
            'type' => 'debit',
            'amount' => '9000.00',
            'reason' => 'wallet_expiry',
        ]);
        $this->assertSame('expired', $credit->fresh()->lifetimeStatus());
    }

    public function test_the_sweep_is_idempotent(): void
    {
        $customer = $this->makeCustomer();
        $credit = $this->expiringCredit($customer, 9000);

        $this->travelTo(now()->addDays(11));
        $this->artisan('sell:wallet-expire')->assertSuccessful();
        $this->artisan('sell:wallet-expire')->assertSuccessful();

        $this->assertSame('0.00', $customer->fresh()->wallet_balance);
        $this->assertCount(1, WalletTransaction::where('reason', 'wallet_expiry')->get());
    }

    public function test_a_credit_still_within_its_window_is_not_swept(): void
    {
        $customer = $this->makeCustomer();
        $this->expiringCredit($customer, 9000);

        $this->travelTo(now()->addDays(9));
        $this->artisan('sell:wallet-expire')->assertSuccessful();

        $this->assertSame('9000.00', $customer->fresh()->wallet_balance);
        $this->assertCount(0, WalletTransaction::where('reason', 'wallet_expiry')->get());
    }

    public function test_only_the_remaining_balance_is_struck_after_partial_use(): void
    {
        $customer = $this->makeCustomer();
        $credit = $this->expiringCredit($customer, 9000);
        app(WalletService::class)->debit($customer, 3000, 'order_purchase');

        $this->assertSame('6000.00', $customer->fresh()->wallet_balance);

        $this->travelTo(now()->addDays(10));
        $this->artisan('sell:wallet-expire')->assertSuccessful();

        $this->assertSame('0.00', $customer->fresh()->wallet_balance);
        $this->assertNotNull($credit->fresh()->expired_at);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $customer->id,
            'type' => 'debit',
            'amount' => '6000.00',
            'reason' => 'wallet_expiry',
        ]);
    }

    public function test_the_expiry_debit_hard_links_back_to_its_credit(): void
    {
        $customer = $this->makeCustomer();
        $credit = $this->expiringCredit($customer, 9000);

        $this->travelTo(now()->addDays(10));
        $this->artisan('sell:wallet-expire')->assertSuccessful();

        $debit = WalletTransaction::where('reason', 'wallet_expiry')->firstOrFail();
        $this->assertSame($credit->getMorphClass(), $debit->reference_type);
        $this->assertSame($credit->id, $debit->reference_id);
    }

    public function test_a_full_settlement_credit_expires_through_the_real_pipeline(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $sell = $this->openedSellRequest($customer, $vendor, 8000);
        $this->sellService()->closeBidding($sell);
        $this->assertTrue($this->sellService()->settle($sell->fresh(), 10000));

        $this->travelTo($sell->fresh()->settlement_at->copy()->addDays(SellRequest::WALLET_CREDIT_VALID_DAYS)->addMinute());
        $this->artisan('sell:wallet-expire')->assertSuccessful();

        $this->assertSame('0.00', $customer->fresh()->wallet_balance);
        $this->assertNotNull($sell->fresh()->walletCredit->expired_at);
        $this->assertSame(SellRequest::STATUS_COMPLETED, $sell->fresh()->status);
    }
}
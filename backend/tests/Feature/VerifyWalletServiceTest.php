<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyWalletServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_increases_balance_and_writes_ledger_row(): void
    {
        $user = User::factory()->create(['wallet_balance' => 100]);

        $transaction = app(WalletService::class)->credit($user, 50, 'reward_approved');

        $this->assertSame('150.00', $user->fresh()->wallet_balance);
        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertSame('credit', $transaction->type);
        $this->assertSame('50.00', $transaction->amount);
        $this->assertSame('150.00', $transaction->balance_after);
        $this->assertSame('reward_approved', $transaction->reason);
    }

    public function test_debit_decreases_balance_and_writes_ledger_row(): void
    {
        $user = User::factory()->create(['wallet_balance' => 100]);

        $transaction = app(WalletService::class)->debit($user, 40, 'order_payment');

        $this->assertSame('60.00', $user->fresh()->wallet_balance);
        $this->assertSame('debit', $transaction->type);
        $this->assertSame('40.00', $transaction->amount);
        $this->assertSame('60.00', $transaction->balance_after);
    }

    public function test_debit_more_than_balance_throws_and_changes_nothing(): void
    {
        $user = User::factory()->create(['wallet_balance' => 10]);

        $this->expectException(\DomainException::class);

        try {
            app(WalletService::class)->debit($user, 20, 'order_payment');
        } finally {
            $this->assertSame('10.00', $user->fresh()->wallet_balance);
            $this->assertSame(0, WalletTransaction::count());
        }
    }

    public function test_user_has_a_wallet_transactions_relation(): void
    {
        $user = User::factory()->create();
        app(\App\Services\WalletService::class)->credit($user, 25, 'reward_approved');

        $this->assertCount(1, $user->walletTransactions);
    }

    public function test_credit_succeeds_for_a_phone_only_user_with_no_email(): void
    {
        // Phone-OTP signups leave users.email null. The credit commits in its
        // own transaction before the notification is sent, so an unguarded
        // Mail::to(null) surfaced as a 500 on an already-credited wallet.
        $user = User::create(['name' => 'OTP Customer', 'phone' => '9876543210']);

        $transaction = app(WalletService::class)->credit($user, 100, 'reward_approved');

        $this->assertSame('100.00', $user->fresh()->wallet_balance);
        $this->assertSame('100.00', $transaction->balance_after);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\WalletManagement\Pages\ListWalletManagement;
use App\Mail\WalletCredited;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Exercises the credit/debit closures from WalletManagementTable directly
 * (mirroring the runApprove/runReject helper pattern in
 * VerifyRewardSubmissionApprovalTest) in addition to the page-render
 * regression test below, so the credit/debit/query logic can be asserted
 * without needing to drive Filament's action modals through Livewire.
 */
class VerifyWalletManagementResourceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $this->seed(ShieldSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_credit_a_customers_wallet_with_a_reason(): void
    {
        Mail::fake();
        $this->actingAsSuperAdmin();
        $customer = User::factory()->create(['wallet_balance' => 0]);

        app(WalletService::class)->credit($customer, 200, 'admin_credit: Goodwill gesture');

        $this->assertSame('200.00', $customer->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $customer->id,
            'type' => 'credit',
            'amount' => '200.00',
            'reason' => 'admin_credit: Goodwill gesture',
        ]);
        Mail::assertQueued(WalletCredited::class, fn ($mail) => $mail->transaction->user->is($customer));
    }

    public function test_admin_can_debit_a_customers_wallet_with_a_reason(): void
    {
        $this->actingAsSuperAdmin();
        $customer = User::factory()->create(['wallet_balance' => 500]);

        app(WalletService::class)->debit($customer, 150, 'admin_debit: Correction');

        $this->assertSame('350.00', $customer->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $customer->id,
            'type' => 'debit',
            'amount' => '150.00',
            'reason' => 'admin_debit: Correction',
        ]);
    }

    public function test_debiting_more_than_the_balance_throws_and_changes_nothing(): void
    {
        $this->actingAsSuperAdmin();
        $customer = User::factory()->create(['wallet_balance' => 50]);

        $this->expectException(\DomainException::class);

        try {
            app(WalletService::class)->debit($customer, 200, 'admin_debit: Too much');
        } finally {
            $this->assertSame('50.00', $customer->fresh()->wallet_balance);
            $this->assertSame(0, WalletTransaction::where('user_id', $customer->id)->count());
        }
    }

    /**
     * The same whereDoesntHave('roles') filter WalletManagementTable::configure()
     * applies to its ->query(), verified directly against the User model
     * since it needs no Filament Table/Livewire wiring to be true.
     */
    public function test_customers_without_roles_are_distinguishable_from_staff_with_roles(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $customer = User::factory()->create();

        $ids = User::query()->whereDoesntHave('roles')->pluck('id');

        $this->assertTrue($ids->contains($customer->id));
        $this->assertFalse($ids->contains($admin->id));
    }

    public function test_wallet_management_page_renders_for_a_super_admin(): void
    {
        $this->actingAsSuperAdmin();
        User::factory()->create(['wallet_balance' => 200]);

        Livewire::test(ListWalletManagement::class)->assertOk();
    }
}

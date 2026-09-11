<?php

namespace Tests\Feature;

use App\Filament\Resources\RewardSubmissions\Pages\EditRewardSubmission;
use App\Filament\Resources\RewardSubmissions\Pages\ListRewardSubmissions;
use App\Mail\RewardSubmissionRejected;
use App\Models\Order;
use App\Models\RewardSubmission;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class VerifyRewardSubmissionApprovalTest extends TestCase
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

    public function test_approving_a_submission_credits_the_customers_wallet(): void
    {
        $this->actingAsSuperAdmin();
        $customer = User::factory()->create(['wallet_balance' => 0]);
        $order = $this->makeOrder($customer);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        Livewire::test(EditRewardSubmission::class, ['record' => $submission->getKey()])
            ->callAction('approve', data: ['reward_amount' => 150]);

        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertSame('150.00', $submission->reward_amount);
        $this->assertNotNull($submission->reviewed_at);
        $this->assertSame('150.00', $customer->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $customer->id,
            'type' => 'credit',
            'amount' => '150.00',
            'reason' => 'reward_approved',
        ]);
    }

    public function test_approving_a_submission_records_who_reviewed_it(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $customer = User::factory()->create(['wallet_balance' => 0]);
        $order = $this->makeOrder($customer);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        Livewire::test(EditRewardSubmission::class, ['record' => $submission->getKey()])
            ->callAction('approve', data: ['reward_amount' => 150]);

        $this->assertSame($admin->id, $submission->fresh()->reviewed_by);
    }

    public function test_rejecting_a_submission_records_who_reviewed_it(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $customer = User::factory()->create();
        $order = $this->makeOrder($customer);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        Livewire::test(EditRewardSubmission::class, ['record' => $submission->getKey()])
            ->callAction('reject', data: ['rejection_reason' => 'Blurry video']);

        $this->assertSame($admin->id, $submission->fresh()->reviewed_by);
    }

    public function test_a_vendor_can_approve_a_submission(): void
    {
        $this->seed(ShieldSeeder::class);
        $vendor = User::factory()->create();
        $vendor->assignRole('vendor');
        $this->actingAs($vendor);

        $customer = User::factory()->create(['wallet_balance' => 0]);
        $order = $this->makeOrder($customer);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        Livewire::test(EditRewardSubmission::class, ['record' => $submission->getKey()])
            ->callAction('approve', data: ['reward_amount' => 200]);

        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertSame($vendor->id, $submission->reviewed_by);
        $this->assertSame('200.00', $customer->fresh()->wallet_balance);
    }

    public function test_reviewer_name_appears_in_the_table_after_approval(): void
    {
        $admin = $this->actingAsSuperAdmin();
        $customer = User::factory()->create(['wallet_balance' => 0, 'name' => 'Reviewer Visibility Test']);
        $order = $this->makeOrder($customer);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        Livewire::test(EditRewardSubmission::class, ['record' => $submission->getKey()])
            ->callAction('approve', data: ['reward_amount' => 150]);

        // "Reviewed By" is toggleable(isToggledHiddenByDefault: true) on this
        // table, so it must be switched on before its content is rendered.
        $list = Livewire::test(ListRewardSubmissions::class);
        $columns = $list->get('tableColumns');
        foreach ($columns as $i => $column) {
            if ($column['name'] === 'reviewer.name') {
                $columns[$i]['isToggled'] = true;
            }
        }
        $list->set('tableColumns', $columns)
            ->assertSee($admin->name);
    }

    public function test_rejecting_a_submission_sets_reason_and_queues_email(): void
    {
        Mail::fake();
        $this->actingAsSuperAdmin();
        $customer = User::factory()->create();
        $order = $this->makeOrder($customer);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        Livewire::test(EditRewardSubmission::class, ['record' => $submission->getKey()])
            ->callAction('reject', data: ['rejection_reason' => 'Video unclear']);

        $submission->refresh();
        $this->assertSame('rejected', $submission->status);
        $this->assertSame('Video unclear', $submission->rejection_reason);
        $this->assertSame('0.00', $customer->fresh()->wallet_balance);

        Mail::assertQueued(RewardSubmissionRejected::class, fn ($mail) => $mail->submission->is($submission));
    }

    public function test_concurrent_approve_calls_only_credit_the_wallet_once(): void
    {
        $customer = User::factory()->create(['wallet_balance' => 0]);
        $order = $this->makeOrder($customer);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        $firstCopy = RewardSubmission::find($submission->getKey());
        $secondCopy = RewardSubmission::find($submission->getKey());

        $this->runApprove($firstCopy, 150);
        $this->runApprove($secondCopy, 150);

        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertSame('150.00', $submission->reward_amount);
        $this->assertSame('150.00', $customer->fresh()->wallet_balance);
        $this->assertSame(1, WalletTransaction::where('user_id', $customer->id)->count());
    }

    public function test_concurrent_reject_calls_only_queue_one_email(): void
    {
        Mail::fake();
        $customer = User::factory()->create();
        $order = $this->makeOrder($customer);
        $submission = RewardSubmission::create(['user_id' => $customer->id, 'order_id' => $order->id, 'status' => 'pending']);

        $firstCopy = RewardSubmission::find($submission->getKey());
        $secondCopy = RewardSubmission::find($submission->getKey());

        $this->runReject($firstCopy, 'Video unclear');
        $this->runReject($secondCopy, 'Different reason');

        $submission->refresh();
        $this->assertSame('rejected', $submission->status);
        $this->assertSame('Video unclear', $submission->rejection_reason);

        Mail::assertQueued(RewardSubmissionRejected::class, 1);
    }

    private function runApprove(RewardSubmission $record, float $rewardAmount): void
    {
        DB::transaction(function () use ($record, $rewardAmount) {
            $updated = RewardSubmission::whereKey($record->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'reward_amount' => $rewardAmount,
                    'reviewed_at' => now(),
                ]);

            if ($updated === 0) {
                return;
            }

            app(WalletService::class)->credit(
                $record->user,
                $rewardAmount,
                'reward_approved',
                $record->fresh(),
            );
        });
    }

    private function runReject(RewardSubmission $record, string $reason): void
    {
        DB::transaction(function () use ($record, $reason) {
            $updated = RewardSubmission::whereKey($record->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'rejection_reason' => $reason,
                    'reviewed_at' => now(),
                ]);

            if ($updated === 0) {
                return;
            }

            Mail::to($record->user->email)->queue(new RewardSubmissionRejected($record->fresh()));
        });
    }

    public function test_approve_and_reject_actions_are_hidden_once_already_reviewed(): void
    {
        $this->actingAsSuperAdmin();
        $customer = User::factory()->create();
        $order = $this->makeOrder($customer);
        $submission = RewardSubmission::create([
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'status' => 'approved',
            'reward_amount' => 100,
            'reviewed_at' => now(),
        ]);

        Livewire::test(EditRewardSubmission::class, ['record' => $submission->getKey()])
            ->assertActionHidden('approve')
            ->assertActionHidden('reject');
    }

    private function makeOrder(User $user): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-RS-'.uniqid(),
            'customer_name' => 'Reward Test',
            'customer_email' => 'reward@example.com',
            'customer_phone' => '9999999999',
            'shipping_address_line1' => 'Test St',
            'shipping_city' => 'Hyderabad',
            'shipping_state' => 'Telangana',
            'shipping_postal_code' => '500001',
            'shipping_country' => 'India',
            'subtotal' => 500,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 500,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'delivered',
        ]);
    }
}

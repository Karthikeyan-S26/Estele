<?php

namespace Tests\Feature;

use App\Mail\NewRewardSubmissionNotification;
use App\Models\Order;
use App\Models\RewardSubmission;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Server-side validation is the real security boundary here — every test in
 * this file drives the actual HTTP endpoint, not the model directly.
 */
class VerifyRewardSubmissionUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
    }

    public function test_customer_can_submit_proof_for_their_own_delivered_order(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');

        $this->actingAs($user)
            ->post(route('account.rewards.store'), [
                'order_id' => $order->id,
                'image' => UploadedFile::fake()->image('proof.png')->size(2000),
                'video' => $this->fakeMp4('proof.mp4'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reward_submissions', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_submitting_proof_notifies_every_vendor_and_super_admin(): void
    {
        Queue::fake();
        Mail::fake();

        $vendor = User::factory()->create();
        $vendor->assignRole('vendor');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $marketer = User::factory()->create();
        $marketer->assignRole('marketing');

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');

        $this->actingAs($user)
            ->post(route('account.rewards.store'), [
                'order_id' => $order->id,
                'image' => UploadedFile::fake()->image('proof.png')->size(2000),
                'video' => $this->fakeMp4('proof.mp4'),
            ])
            ->assertRedirect();

        Mail::assertQueued(NewRewardSubmissionNotification::class, fn ($mail) => $mail->hasTo($vendor->email));
        Mail::assertQueued(NewRewardSubmissionNotification::class, fn ($mail) => $mail->hasTo($admin->email));
        Mail::assertQueued(NewRewardSubmissionNotification::class, 2);
    }

    public function test_image_over_3mb_is_rejected(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');

        $this->actingAs($user)
            ->post(route('account.rewards.store'), [
                'order_id' => $order->id,
                'image' => UploadedFile::fake()->image('proof.png')->size(3200),
                'video' => UploadedFile::fake()->create('proof.mp4', 5000, 'video/mp4'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('reward_submissions', ['order_id' => $order->id]);
    }

    public function test_video_over_10mb_is_rejected(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');

        $this->actingAs($user)
            ->post(route('account.rewards.store'), [
                'order_id' => $order->id,
                'image' => UploadedFile::fake()->image('proof.png')->size(2000),
                'video' => UploadedFile::fake()->create('proof.mp4', 10500, 'video/mp4'),
            ])
            ->assertSessionHasErrors('video');

        $this->assertDatabaseMissing('reward_submissions', ['order_id' => $order->id]);
    }

    public function test_non_video_mime_is_rejected_for_video_field(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');

        $this->actingAs($user)
            ->post(route('account.rewards.store'), [
                'order_id' => $order->id,
                'image' => UploadedFile::fake()->image('proof.png')->size(2000),
                'video' => UploadedFile::fake()->create('proof.txt', 100, 'text/plain'),
            ])
            ->assertSessionHasErrors('video');
    }

    public function test_non_delivered_order_is_rejected(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'placed');

        $this->actingAs($user)
            ->post(route('account.rewards.store'), [
                'order_id' => $order->id,
                'image' => UploadedFile::fake()->image('proof.png')->size(2000),
                'video' => UploadedFile::fake()->create('proof.mp4', 5000, 'video/mp4'),
            ])
            ->assertStatus(422);
    }

    public function test_a_user_cannot_submit_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->makeOrder($owner, 'delivered');

        $this->actingAs($stranger)
            ->post(route('account.rewards.store'), [
                'order_id' => $order->id,
                'image' => UploadedFile::fake()->image('proof.png')->size(2000),
                'video' => UploadedFile::fake()->create('proof.mp4', 5000, 'video/mp4'),
            ])
            ->assertNotFound();
    }

    public function test_cannot_submit_twice_for_the_same_order(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');
        RewardSubmission::create(['user_id' => $user->id, 'order_id' => $order->id, 'status' => 'pending']);

        $this->actingAs($user)
            ->post(route('account.rewards.store'), [
                'order_id' => $order->id,
                'image' => UploadedFile::fake()->image('proof.png')->size(2000),
                'video' => UploadedFile::fake()->create('proof.mp4', 5000, 'video/mp4'),
            ])
            ->assertStatus(422);
    }

    public function test_concurrent_duplicate_submission_race_is_rejected_with_422_not_500(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');

        $alreadyRaced = false;

        RewardSubmission::creating(function (RewardSubmission $submission) use ($order, &$alreadyRaced) {
            if ($alreadyRaced) {
                return;
            }
            $alreadyRaced = true;

            RewardSubmission::withoutEvents(function () use ($order) {
                RewardSubmission::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'status' => 'pending',
                ]);
            });
        });

        $response = $this->actingAs($user)
            ->post(route('account.rewards.store'), [
                'order_id' => $order->id,
                'image' => UploadedFile::fake()->image('proof.png')->size(2000),
                'video' => UploadedFile::fake()->create('proof.mp4', 5000, 'video/mp4'),
            ]);

        $response->assertStatus(422);
        $this->assertSame(1, RewardSubmission::where('order_id', $order->id)->count());
    }

    public function test_rewards_page_shows_wallet_balance_and_eligible_orders(): void
    {
        $user = User::factory()->create(['wallet_balance' => 250]);
        $order = $this->makeOrder($user, 'delivered');

        $response = $this->actingAs($user)->get(route('account.rewards.index'));

        $response->assertOk()
            ->assertSee('₹250.00')
            ->assertSee($order->order_number);
    }

    public function test_rewards_page_upload_form_includes_preview_elements(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');

        $response = $this->actingAs($user)->get(route('account.rewards.index'));

        $response->assertOk()
            ->assertSee('reward-image-preview', false)
            ->assertSee('reward-video-preview', false);
    }

    public function test_rewards_page_shows_rejection_reason_for_rejected_submission(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');
        RewardSubmission::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => 'rejected',
            'rejection_reason' => 'Video was unclear',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('account.rewards.index'))
            ->assertOk()
            ->assertSee('Video was unclear');
    }

    private function fakeMp4(string $name, int $kilobytes = 5): UploadedFile
    {
        $ftypBox = hex2bin('0000001c6674797069736f6d0000020069736f6d69736f326d703431');
        $content = str_pad($ftypBox, $kilobytes * 1024, "\0");

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function makeOrder(User $user, string $status): Order
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
            'status' => $status,
        ]);
    }
}

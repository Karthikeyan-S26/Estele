<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\RewardSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VerifyRewardSubmissionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_a_submission_with_image_and_video_media(): void
    {
        // Media conversions (e.g. the "thumb" webp conversion) are queued and
        // run in a job; faking the queue keeps this test from executing that
        // job synchronously, since this environment's gd build has no webp
        // support compiled in.
        Queue::fake();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');

        $submission = RewardSubmission::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        $submission->addMedia(UploadedFile::fake()->image('proof.png', 100, 100)->size(500))
            ->toMediaCollection('image');
        // media-library detects the mime type by sniffing the file's actual
        // bytes on disk, not the client-reported mime — so the fake upload
        // needs real MP4 content (a minimal ftyp box) rather than an empty
        // placeholder file, or it would be rejected as application/x-empty.
        $submission->addMedia(UploadedFile::fake()->createWithContent(
            'proof.mp4',
            hex2bin('0000001c6674797069736f6d0000020069736f6d69736f326d703431')
        ))->toMediaCollection('video');

        $this->assertTrue($submission->fresh()->hasMedia('image'));
        $this->assertTrue($submission->fresh()->hasMedia('video'));
        $this->assertSame('pending', $submission->status);
        $this->assertSame($user->id, $submission->user->id);
        $this->assertSame($order->id, $submission->order->id);
    }

    public function test_only_one_submission_per_order_is_allowed_at_the_database_level(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'delivered');
        RewardSubmission::create(['user_id' => $user->id, 'order_id' => $order->id, 'status' => 'pending']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        RewardSubmission::create(['user_id' => $user->id, 'order_id' => $order->id, 'status' => 'pending']);
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

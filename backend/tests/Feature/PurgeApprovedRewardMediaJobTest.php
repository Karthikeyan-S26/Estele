<?php

namespace Tests\Feature;

use App\Jobs\PurgeApprovedRewardMediaJob;
use App\Models\Order;
use App\Models\RewardSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeApprovedRewardMediaJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_is_deleted_only_for_submissions_approved_over_a_day_ago(): void
    {
        Storage::fake('original_images');

        $old = $this->submission('approved', now()->subHours(25));
        $recent = $this->submission('approved', now()->subHours(2));
        $rejected = $this->submission('rejected', now()->subDays(3));
        $pending = $this->submission('pending', null);

        $oldPath = $old->getFirstMedia('video')->getPathRelativeToRoot();

        (new PurgeApprovedRewardMediaJob)->handle();

        $this->assertCount(0, $old->fresh()->getMedia('video'));
        Storage::disk('original_images')->assertMissing($oldPath);
        $this->assertSame('approved', $old->fresh()->status);

        $this->assertCount(1, $recent->fresh()->getMedia('video'));
        $this->assertCount(1, $rejected->fresh()->getMedia('video'));
        $this->assertCount(1, $pending->fresh()->getMedia('video'));
    }

    private function submission(string $status, $reviewedAt): RewardSubmission
    {
        $user = User::factory()->create();

        $submission = RewardSubmission::create([
            'user_id' => $user->id,
            'order_id' => $this->makeOrder($user)->id,
            'status' => $status,
            'reviewed_at' => $reviewedAt,
        ]);

        $ftyp = hex2bin('0000001c6674797069736f6d0000020069736f6d69736f326d703431');
        $submission->addMedia(UploadedFile::fake()->createWithContent('proof.mp4', str_pad($ftyp, 5120, "\0")))
            ->toMediaCollection('video');

        return $submission;
    }

    private function makeOrder(User $user): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-PR-'.uniqid(),
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

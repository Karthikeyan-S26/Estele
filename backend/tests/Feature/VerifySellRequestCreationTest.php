<?php

namespace Tests\Feature;

use App\Models\SellBid;
use App\Models\SellRequest;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * Customer Sell Request creation over POST /api/account/sell/requests — media rules,
 * validation, storage, ownership and initial lifecycle state, all driven
 * through the real HTTP endpoint + TokenGuard (no service shortcut).
 */
class VerifySellRequestCreationTest extends TestCase
{
    use RefreshDatabase;
    use SellTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        Storage::fake('public');
    }

    private function validPayload(array $media = []): array
    {
        return array_merge([
            'item_type' => 'ring',
            'description' => 'Gold-plated ring, light wear',
            'city' => 'Hyderabad',
        ], $media);
    }

    public function test_an_authenticated_customer_can_create_a_sell_request(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'video' => $this->base64Media('video/mp4', 1024),
            ]));

        $response->assertCreated();
        $this->assertDatabaseHas('sell_requests', [
            'user_id' => $customer->id,
            'item_type' => 'ring',
            'status' => SellRequest::STATUS_PENDING_BIDS,
        ]);
    }

    public function test_the_video_is_compulsory(): void
    {
        $customer = $this->makeCustomer();

        $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload(['image' => $this->base64Media('image/jpeg', 1024)]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('video');

        $this->assertDatabaseCount('sell_requests', 0);
    }

    public function test_the_image_is_optional(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'video' => $this->base64Media('video/mp4', 1024),
            ]));

        $response->assertCreated();
        $sell = SellRequest::firstOrFail();
        $this->assertNull($sell->image_path);
        $this->assertNotNull($sell->video_path);
        $this->assertNull($response->json('data.image_url'));
    }

    public function test_a_valid_image_and_video_are_accepted_and_stored(): void
    {
        $customer = $this->makeCustomer();

        $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'image' => $this->base64Media('image/webp', 2048),
                'video' => $this->base64Media('video/mp4', 2048),
            ]))
            ->assertCreated();

        $sell = SellRequest::firstOrFail();
        Storage::disk('public')->assertExists($sell->image_path);
        Storage::disk('public')->assertExists($sell->video_path);
        $this->assertStringEndsWith('.webp', $sell->image_path);
        $this->assertStringEndsWith('.mp4', $sell->video_path);
    }

    public function test_an_invalid_image_mime_is_rejected(): void
    {
        $customer = $this->makeCustomer();

        $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'image' => $this->base64Media('image/gif', 1024),
                'video' => $this->base64Media('video/mp4', 1024),
            ]))
            ->assertStatus(422)
            ->assertJson(['message' => 'IMAGE must be a supported file type.']);

        $this->assertDatabaseCount('sell_requests', 0);
    }

    public function test_an_invalid_video_mime_is_rejected(): void
    {
        $customer = $this->makeCustomer();

        $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'video' => $this->base64Media('video/quicktime', 1024),
            ]))
            ->assertStatus(422)
            ->assertJson(['message' => 'VIDEO must be a supported file type.']);

        $this->assertDatabaseCount('sell_requests', 0);
    }

    public function test_an_image_larger_than_3mb_is_rejected(): void
    {
        $customer = $this->makeCustomer();

        $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'image' => $this->base64Media('image/jpeg', 3 * 1024 * 1024 + 1),
                'video' => $this->base64Media('video/mp4', 1024),
            ]))
->assertStatus(422)
            ->assertJson(['message' => 'The image file is too large.']);

        $this->assertDatabaseCount('sell_requests', 0);
    }

    public function test_a_video_larger_than_20mb_is_rejected(): void
    {
        $customer = $this->makeCustomer();

        $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'video' => $this->base64Media('video/mp4', 20 * 1024 * 1024 + 1),
            ]))
->assertStatus(422)
            ->assertJson(['message' => 'The video file is too large.']);

        $this->assertDatabaseCount('sell_requests', 0);
    }

    public function test_garbage_base64_is_rejected(): void
    {
        $customer = $this->makeCustomer();

        $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'video' => ['mime' => 'video/mp4', 'data' => '%%%not-base64%%%'],
            ]))
            ->assertStatus(422)
            ->assertJson(['message' => 'Could not decode the video file.']);

        $this->assertDatabaseCount('sell_requests', 0);
    }

    public function test_an_unauthenticated_customer_is_rejected(): void
    {
        $this->postJson('/api/account/sell/requests', $this->validPayload([
            'video' => $this->base64Media('video/mp4', 1024),
        ]))->assertStatus(401);

        $this->assertDatabaseCount('sell_requests', 0);
    }

    public function test_the_request_number_is_generated(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'video' => $this->base64Media('video/mp4', 1024),
            ]));

        $response->assertCreated();
        $requestNumber = $response->json('data.request_number');
        $this->assertMatchesRegularExpression('/^SRF-\d{8}-[A-Z0-9]{4}$/', $requestNumber);
        $this->assertDatabaseHas('sell_requests', ['request_number' => $requestNumber]);
    }

    public function test_initial_status_and_bidding_window_are_correct(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', $this->validPayload([
                'video' => $this->base64Media('video/mp4', 1024),
            ]));

        $sell = SellRequest::firstOrFail();
        $this->assertSame(SellRequest::STATUS_PENDING_BIDS, $sell->status);
        $this->assertSame(SellRequest::BID_DURATION_HOURS, 3);
        $this->assertNotNull($sell->bids_start_at);
        $this->assertNotNull($sell->bids_end_at);
        $this->assertTrue($sell->bids_end_at->equalTo($sell->bids_start_at->copy()->addHours(SellRequest::BID_DURATION_HOURS)));
        $this->assertFalse($sell->isBiddingOpen());
        $this->assertFalse($response->json('data.bidding_open'));
        $this->assertNull($response->json('data.highest_bid_amount'));
    }

    public function test_a_customer_can_view_their_own_request(): void
    {
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer);

        $this->withToken($this->customerApiToken($customer))
            ->getJson('/api/account/sell/requests/'.$sell->request_number)
            ->assertOk()
            ->assertJsonPath('data.request_number', $sell->request_number)
            ->assertJsonPath('data.status', SellRequest::STATUS_PENDING_BIDS)
            ->assertJsonPath('data.item_type', 'ring');
    }

    public function test_a_customer_cannot_view_another_customers_request(): void
    {
        $owner = $this->makeCustomer();
        $stranger = $this->makeCustomer();
        $sell = $this->createSellRequest($owner);

        $this->withToken($this->customerApiToken($stranger))
            ->getJson('/api/account/sell/requests/'.$sell->request_number)
            ->assertNotFound();
    }

    public function test_the_index_lists_only_own_requests(): void
    {
        $owner = $this->makeCustomer();
        $other = $this->makeCustomer();
        $sell = $this->createSellRequest($owner);
        $this->createSellRequest($other, ['item_type' => 'necklace']);

        $response = $this->withToken($this->customerApiToken($owner))
            ->getJson('/api/account/sell/requests')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($sell->request_number, $response->json('data.0.request_number'));
    }

    public function test_a_customer_request_is_not_created_for_an_invalid_item_type(): void
    {
        $customer = $this->makeCustomer();

        $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', array_merge($this->validPayload([
                'video' => $this->base64Media('video/mp4', 1024),
            ]), ['item_type' => 'not-a-real-type']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('item_type');

        $this->assertDatabaseCount('sell_requests', 0);
    }

    public function test_sell_request_creation_is_recorded_in_the_audit_log(): void
    {
        $customer = $this->makeCustomer();
        $this->createSellRequest($customer);

        $sell = SellRequest::firstOrFail();
        $this->assertDatabaseHas('sell_audit_logs', [
            'sell_request_id' => $sell->id,
            'actor_type' => 'App\\Models\\User',
            'actor_id' => $customer->id,
            'event' => 'created',
            'from_status' => null,
            'to_status' => SellRequest::STATUS_PENDING_BIDS,
        ]);
    }
}

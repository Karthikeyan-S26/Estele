<?php

namespace Tests\Feature;

use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SellTestHelpers;
use Tests\TestCase;

/**
 * Media wiring and its privacy surface: owner-only API URLs, token-scoped
 * vendor views, and the absence of any numeric-ID media endpoint (the vendor
 * flow deliberately uses unguessable tokens, not IDs).
 */
class VerifySellMediaSecurityTest extends TestCase
{
    use RefreshDatabase;
    use SellTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);
        Storage::fake('public');
    }

    private function createSellRequestWithMedia($customer)
    {
        return $this->withToken($this->customerApiToken($customer))
            ->postJson('/api/account/sell/requests', [
                'item_type' => 'chain',
                'description' => 'Chain with media',
                'city' => 'Hyderabad',
                'image' => $this->base64Media('image/jpeg', 2048),
                'video' => $this->base64Media('video/mp4', 2048),
            ])
            ->assertCreated();
    }

    public function test_the_owner_sees_their_media_urls(): void
    {
        $customer = $this->makeCustomer();
        $response = $this->createSellRequestWithMedia($customer);
        $requestNumber = $response->json('data.request_number');
        $sellId = \App\Models\SellRequest::where('request_number', $requestNumber)->value('id');

        $this->assertStringContainsString('/storage/sell-requests/'.$sellId.'/image', $response->json('data.image_url'));
        $this->assertStringContainsString('/storage/sell-requests/'.$sellId.'/video', $response->json('data.video_url'));
        $this->assertStringEndsWith('.jpeg', $response->json('data.image_url'));
        $this->assertStringEndsWith('.mp4', $response->json('data.video_url'));

        Storage::disk('public')->assertExists("sell-requests/{$sellId}/image.jpeg");
        Storage::disk('public')->assertExists("sell-requests/{$sellId}/video.mp4");
    }

    public function test_another_customer_cannot_reach_the_media_via_the_api(): void
    {
        $owner = $this->makeCustomer();
        $stranger = $this->makeCustomer();
        $image = $this->base64Media('image/jpeg', 2048);
        $video = $this->base64Media('video/mp4', 2048);
        $sell = $this->sellService()->createForCustomer(
            $owner,
            ['item_type' => 'chain', 'description' => 'Chain', 'city' => 'Hyderabad'],
            $image['data'], 'image/jpeg',
            $video['data'], 'video/mp4',
        );

        // A single outgoing bearer request per user — the token guard caches
        // who it resolved within one request lifecycle, so cross-user checks
        // need a clean auth state, exactly like a real API client would have.
        $this->withToken($this->customerApiToken($stranger))
            ->getJson('/api/account/sell/requests/'.$sell->request_number)
            ->assertNotFound();
    }

    public function test_an_accepted_vendor_sees_the_request_media_on_the_invitation_page(): void
    {
        $vendor = $this->makeVendor();
        $customer = $this->makeCustomer();
        $sell = $this->createSellRequest($customer, ['item_type' => 'bracelet']);
        $this->sellService()->acceptInvitation($this->invitationFor($sell, $vendor));

        // Wire a fake image onto the request to mimic an uploaded photo.
        $sell->update(['image_path' => "sell-requests/{$sell->id}/image.webp"]);

        $page = $this->get(route('sell.vendor.invitation', $this->invitationFor($sell, $vendor)->token))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('/storage/sell-requests/'.$sell->id.'/image.webp', $page);
    }

    public function test_there_is_no_numeric_id_media_endpoint(): void
    {
        // The tokenized web flow and the bearer-token API are the only media
        // surfaces; a fetch-by-database-id route would defeat the token design.
        $this->assertNull(Route::getRoutes()->getByName('sell.media.show'));
        $this->assertNull(Route::getRoutes()->getByName('sell.vendor.media.show'));

        $customer = $this->makeCustomer();
        $this->withToken($this->customerApiToken($customer))
            ->getJson('/api/account/sell/requests/1')
            ->assertNotFound();
        $this->get('/sell/vendors/media/1')->assertNotFound();
    }

    public function test_an_unknown_request_number_is_a_404_not_a_500(): void
    {
        $customer = $this->makeCustomer();

        $this->withToken($this->customerApiToken($customer))
            ->getJson('/api/account/sell/requests/SRF-00000000-XXXX')
            ->assertNotFound();
    }
}
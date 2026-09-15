<?php

namespace Tests\Feature\Concerns;

use App\Models\SellInvitation;
use App\Models\SellRequest;
use App\Models\User;
use App\Services\SellRequestService;

/**
 * Shared construction helpers for the Old Jewellery buy-back suite. Every test
 * drives the real SellRequestService (never fakes that bypass business rules);
 * only mail/queue/whatsapp side-effects may be faked.
 */
trait SellTestHelpers
{
    protected function makeCustomer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'phone' => '9'.random_int(100000000, 999999999),
        ], $overrides));
    }

    protected function makeVendor(): User
    {
        $vendor = User::factory()->create([
            'phone' => '8'.random_int(100000000, 999999999),
        ]);
        $vendor->assignRole('vendor');

        return $vendor;
    }

    protected function customerApiToken(User $user): string
    {
        return $user->createApiToken();
    }

    protected function sellService(): SellRequestService
    {
        return app(SellRequestService::class);
    }

    /**
     * Create a fully valid sell request through the real service (video only).
     */
    protected function createSellRequest(User $customer, array $overrides = []): SellRequest
    {
        return $this->sellService()->createForCustomer(
            $customer,
            array_merge([
                'item_type' => 'ring',
                'description' => 'Gold-plated ring, light wear',
                'city' => 'Hyderabad',
            ], $overrides),
            null,
            null,
            base64_encode('test-video-bytes'),
            'video/mp4',
        );
    }

    /**
     * A sell request whose bidding has opened (one vendor accepted) and which
     * optionally already carries a bid from that vendor.
     */
    protected function openedSellRequest(User $customer, User $vendor, ?float $initialBid = null): SellRequest
    {
        $sell = $this->createSellRequest($customer);
        $invitation = $sell->invitations()->where('vendor_id', $vendor->id)->firstOrFail();
        $this->sellService()->acceptInvitation($invitation);

        if ($initialBid !== null) {
            $this->sellService()->submitBid($invitation->fresh(), $initialBid);
        }

        return $sell->fresh();
    }

    protected function invitationFor(SellRequest $sell, User $vendor): SellInvitation
    {
        return $sell->invitations()->where('vendor_id', $vendor->id)->firstOrFail();
    }

    protected function base64Media(string $mime, int $bytes): array
    {
        return ['mime' => $mime, 'data' => base64_encode(str_repeat('a', $bytes))];
    }
}
<?php

namespace App\Services\Shipping;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point the storefront/checkout calls for a shipping quote.
 * Which provider actually runs is a Setting (shipping_provider), not code —
 * swapping Shiprocket in and out, or falling back when it's unreachable,
 * never needs a deploy.
 */
class ShippingManager
{
    public function __construct(
        private readonly FlatRateCalculator $flatRate,
        private readonly ShiprocketService $shiprocket,
    ) {}

    /**
     * @return array{fee: float, free_shipping_applied: bool, label: string, estimated: bool}
     *
     * @throws UnserviceableAddressException if the active courier has
     *         positively confirmed it cannot deliver to $postalCode — the
     *         caller (checkout) must stop the order, not charge a flat-rate
     *         stand-in for an address nobody can actually ship to.
     */
    public function quote(float $cartSubtotal, int $itemCount, ?string $postalCode = null): array
    {
        $usesShiprocket = $this->activeProvider() === 'shiprocket' && $this->shiprocket->isConfigured();

        if ($usesShiprocket && $postalCode) {
            try {
                return [...$this->shiprocket->quote($cartSubtotal, $itemCount, $postalCode), 'estimated' => false];
            } catch (UnserviceableAddressException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // Never let a courier API *outage* block checkout — fall
                // back to the flat-rate quote and let the order go through.
                // (A confirmed unserviceable address, above, is different
                // and must not be swallowed the same way.)
                Log::warning('Shiprocket rate check failed, falling back to flat rate.', ['error' => $e->getMessage()]);
            }
        }

        $quote = $this->flatRate->quote($cartSubtotal, $itemCount, $postalCode);

        // The configured provider is pincode-dependent but we don't have one
        // yet (e.g. the cart/checkout summary, shown before the address form
        // is submitted) — this flat number is a stand-in, not what will
        // actually be charged, so the UI must say so rather than imply it's final.
        $quote['estimated'] = $usesShiprocket && ! $postalCode;

        return $quote;
    }

    private function activeProvider(): string
    {
        return $this->settings()['shipping_provider'] ?? 'flat';
    }

    private function settings(): array
    {
        return Cache::remember('site.settings', 3600, fn () => Setting::pluck('value', 'key')->toArray());
    }
}

<?php

namespace App\Services\Shipping;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Default/fallback provider — free above a threshold, flat fee otherwise.
 * Never depends on an external API, so it's always available even when
 * Shiprocket isn't configured or is down.
 */
class FlatRateCalculator implements ShippingRateCalculator
{
    public function quote(float $cartSubtotal, int $itemCount, ?string $postalCode = null): array
    {
        $settings = $this->settings();

        // Blank/missing threshold means "no free-shipping rule configured"
        // (never triggers) — NOT the same as an explicit "0", which an
        // admin would set to mean "everything qualifies, always free".
        // Casting a missing setting straight to (float) 0 would collapse
        // those two very different intents into one.
        $thresholdRaw = $settings['shipping_free_threshold'] ?? null;
        $threshold = ($thresholdRaw === null || $thresholdRaw === '') ? null : (float) $thresholdRaw;
        $flatRate = (float) ($settings['shipping_flat_rate'] ?? 0);

        $fee = ($threshold !== null && $cartSubtotal >= $threshold) ? 0.0 : $flatRate;

        return [
            'fee' => $fee,
            'free_shipping_applied' => $fee <= 0.0,
            'label' => $fee > 0 ? 'Standard shipping' : 'Free shipping',
        ];
    }

    // Same cache key as SiteDataComposer — SettingObserver invalidates it on
    // every admin save, so this never reads stale threshold/rate values.
    private function settings(): array
    {
        return Cache::remember('site.settings', 3600, fn () => Setting::pluck('value', 'key')->toArray());
    }
}

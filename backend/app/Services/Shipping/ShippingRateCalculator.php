<?php

namespace App\Services\Shipping;

interface ShippingRateCalculator
{
    /**
     * @return array{fee: float, free_shipping_applied: bool, label: string}
     */
    public function quote(float $cartSubtotal, int $itemCount, ?string $postalCode = null): array;
}

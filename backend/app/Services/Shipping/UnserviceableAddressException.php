<?php

namespace App\Services\Shipping;

/**
 * Thrown when a courier provider has positively confirmed it cannot deliver
 * to an address — distinct from a generic \Throwable (API down, bad
 * credentials, network timeout), which ShippingManager treats as "fall back
 * to flat rate and let the order through." This one must never be treated
 * that way: silently charging a flat rate for an address nobody can
 * actually deliver to is worse than blocking the order.
 */
class UnserviceableAddressException extends \RuntimeException {}

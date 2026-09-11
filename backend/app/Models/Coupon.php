<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Coupon extends Model
{
    /**
     * Keeps the storefront's public-coupon cache (see SiteDataComposer)
     * correct immediately rather than relying solely on its TTL. Hooks both
     * `saved` (covers normal create/update via Filament) and `updated`
     * (covers `increment('used_count')` at checkout, which fires `updated`
     * but never `saved`).
     */
    protected static function booted(): void
    {
        $forgetPublicCache = fn () => Cache::forget('site.public_coupons');

        static::saved($forgetPublicCache);
        static::updated($forgetPublicCache);
        static::deleted($forgetPublicCache);
    }

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount_amount',
        'min_order_value',
        'scope',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_order_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Coupons eligible to appear in the storefront's "View all coupons" list —
     * doesn't check per-cart applicability (min order value / scope), only
     * whether it's the kind of thing worth advertising to a browsing visitor.
     */
    public function scopeListable(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where('is_public', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
            ->where(fn (Builder $q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper(trim($value)),
        );
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function categories(): BelongsToMany
    {
        // Explicit pivot name: Eloquent's alphabetical-default would be
        // "category_coupon", but the migration created "coupon_category".
        return $this->belongsToMany(Category::class, 'coupon_category');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Short human-readable blurb for the storefront's coupon list, e.g.
     * "10% off, up to ₹500" or "₹100 off orders above ₹999".
     */
    public function summary(): string
    {
        $value = $this->type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off'
            : '₹'.number_format((float) $this->value, 0).' off';

        if ($this->type === 'percent' && $this->max_discount_amount !== null) {
            $value .= ', up to ₹'.number_format((float) $this->max_discount_amount, 0);
        }

        if ($this->min_order_value !== null) {
            $value .= ' on orders above ₹'.number_format((float) $this->min_order_value, 0);
        }

        return $value;
    }

    /**
     * Central validity + discount check, used both when a code is applied to
     * the cart and re-checked (never trusted) at checkout. Never returns a
     * discount larger than the applicable subtotal.
     *
     * @return array{valid: bool, message: ?string, discount: float}
     */
    public function isValidFor(Cart $cart): array
    {
        $invalid = fn (string $message) => ['valid' => false, 'message' => $message, 'discount' => 0.0];

        if (! $this->is_active) {
            return $invalid('This coupon is not active.');
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return $invalid('This coupon is not active yet.');
        }
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return $invalid('This coupon has expired.');
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return $invalid('This coupon has reached its usage limit.');
        }

        $cart->loadMissing('items.product.categories', 'items.variant');
        $items = $cart->items;

        if ($items->isEmpty()) {
            return $invalid('Your cart is empty.');
        }

        $cartSubtotal = $items->sum(fn (CartItem $item) => $item->unitPrice() * $item->quantity);

        if ($this->min_order_value !== null && $cartSubtotal < (float) $this->min_order_value) {
            return $invalid('Your order does not meet the minimum value for this coupon.');
        }

        $applicableSubtotal = match ($this->scope) {
            'products' => $this->applicableSubtotalFor($items, fn (CartItem $item) => in_array($item->product_id, $this->products->pluck('id')->all(), true)),
            'categories' => $this->applicableSubtotalFor($items, fn (CartItem $item) => $item->product->categories->pluck('id')->intersect($this->categories->pluck('id'))->isNotEmpty()),
            default => (float) $cartSubtotal,
        };

        if ($applicableSubtotal <= 0) {
            return $invalid('This coupon does not apply to the items in your cart.');
        }

        $discount = $this->type === 'percent'
            ? $applicableSubtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->type === 'percent' && $this->max_discount_amount !== null) {
            $discount = min($discount, (float) $this->max_discount_amount);
        }

        $discount = min($discount, $applicableSubtotal);

        return ['valid' => true, 'message' => null, 'discount' => round($discount, 2)];
    }

    private function applicableSubtotalFor($items, callable $matches): float
    {
        return (float) $items
            ->filter($matches)
            ->sum(fn (CartItem $item) => $item->unitPrice() * $item->quantity);
    }
}

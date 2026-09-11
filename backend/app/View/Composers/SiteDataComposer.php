<?php

namespace App\View\Composers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Collection as CollectionModel;
use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Popup;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class SiteDataComposer
{
    public function compose(View $view): void
    {
        $cart = Cart::with('coupon')->where('session_id', session()->getId())->first();
        $cartItems = $cart ? $cart->items()->with(['product.media', 'variant'])->get() : collect();

        $cartDiscount = 0.0;
        if ($cart?->coupon) {
            $result = $cart->coupon->isValidFor($cart);
            $cartDiscount = $result['valid'] ? $result['discount'] : 0.0;
        }

        $view->with('cartCount', (int) $cartItems->sum('quantity'));
        $view->with('cartItems', $cartItems);
        $view->with('cartSubtotal', $cartItems->sum(fn ($item) => $item->unitPrice() * $item->quantity));
        $view->with('cartDiscount', $cartDiscount);
        $view->with('cartCouponCode', $cart?->coupon?->code);

        // Laravel's database/file cache stores refuse to unserialize objects by default
        // (config('cache.serializable_classes') === false) — cache plain arrays, not
        // Eloquent models/Collections, to avoid silently getting __PHP_Incomplete_Class back.
        $view->with('navCategories', Cache::remember(
            'site.nav_categories',
            3600,
            fn () => Category::orderBy('sort_order')->get(['id', 'name', 'slug'])->toArray()
        ));

        $view->with('navCollections', Cache::remember(
            'site.nav_collections',
            3600,
            fn () => CollectionModel::active()->ordered()->get(['id', 'name', 'slug'])->toArray()
        ));

        $view->with('siteSettings', Cache::remember(
            'site.settings',
            3600,
            fn () => Setting::pluck('value', 'key')->toArray()
        ));

        $view->with('activeOffers', Cache::remember(
            'site.active_offers',
            3600,
            fn () => Offer::active()->ordered()->get(['text'])->pluck('text')->toArray()
        ));

        // Coupon::booted() forgets this key on every save/update/delete
        // (including the increment('used_count') at checkout), so edits
        // show up immediately. This TTL still has to stay short, though —
        // a coupon's starts_at/expires_at crossing "now" isn't a write, so
        // nothing invalidates the cache when a scheduled coupon goes live
        // or an old one lapses. This is the safety net for that case.
        $view->with('publicCoupons', Cache::remember(
            'site.public_coupons',
            300,
            fn () => Coupon::listable()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Coupon $coupon) => ['code' => $coupon->code, 'summary' => $coupon->summary()])
                ->toArray()
        ));

        $view->with('activePopup', $this->resolveActivePopup());
    }

    /**
     * The eligible *set* of popups (schedule/is_active-gated) is the same for
     * every visitor, so it's safe to cache site-wide — but whether a given
     * popup is right for *this* visitor (new-visitor targeting) depends on a
     * per-request cookie, so that filtering happens after the cache read,
     * not inside it. Caching the whole ordered list (not just the top one)
     * matters: if the top-priority popup is new-visitor-only and this is a
     * returning visitor, a lower-priority "show to everyone" popup must
     * still get a chance to show instead of nothing rendering at all.
     */
    private function resolveActivePopup(): ?array
    {
        $popups = Cache::remember('site.active_popup', 300, function () {
            return Popup::eligible()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Popup $popup) => [
                    'id' => $popup->id,
                    'type' => $popup->type,
                    'trigger' => $popup->trigger,
                    'delay_seconds' => $popup->delay_seconds,
                    'title' => $popup->title,
                    'body' => $popup->body,
                    'cta_label' => $popup->cta_label,
                    'cta_url' => $popup->cta_url,
                    'discount_code' => $popup->discount_code,
                    'show_email_field' => $popup->show_email_field,
                    'target_new_visitors_only' => $popup->target_new_visitors_only,
                    'image_url' => $popup->hasMedia('image') ? $popup->getFirstMediaUrl('image', 'card') : null,
                    'image_alt' => $popup->image_alt_text ?: $popup->title,
                ])
                ->all();
        });

        if (! $popups) {
            return null;
        }

        $isReturningVisitor = request()->hasCookie('estele_visited');

        if (! $isReturningVisitor) {
            Cookie::queue(Cookie::forever('estele_visited', '1'));
        }

        foreach ($popups as $popup) {
            if (! $popup['target_new_visitors_only'] || ! $isReturningVisitor) {
                return $popup;
            }
        }

        return null;
    }
}

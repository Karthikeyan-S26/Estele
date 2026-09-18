<div class="fixed inset-0 z-[210]" data-cart-drawer hidden>
  <div class="absolute inset-0 bg-black/45 opacity-0 transition-opacity duration-300" data-cart-backdrop data-cart-close></div>
  <aside class="absolute right-0 top-0 flex h-full w-full translate-x-full flex-col bg-bagsurface sm:w-[400px] transition-transform duration-300" data-cart-panel aria-label="Shopping cart">
    <div class="flex h-[53px] shrink-0 items-center justify-between bg-white px-3 shadow-[0_1px_4px_rgba(0,0,0,0.1)]">
      <div class="flex items-center gap-1">
        <button class="grid h-10 w-9 place-items-center text-heading" type="button" data-cart-close aria-label="Close cart">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        </button>
        <span class="text-[14px] font-bold text-[#454545]">Your Bag</span>
      </div>
      <span class="flex items-center gap-1.5 pr-2 text-[14px] text-[#454545]">
        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
        Secure
      </span>
    </div>
    <div class="grid flex-1 grid-rows-[minmax(0,1fr)_auto] overflow-hidden" data-cart-body>
      @include('partials.cart-drawer-items', ['items' => $cartItems, 'subtotal' => $cartSubtotal, 'discount' => $cartDiscount, 'couponCode' => $cartCouponCode, 'freeShippingThreshold' => $cartFreeShippingThreshold])
    </div>
  </aside>
</div>

<div class="fixed inset-0 z-[210]" data-cart-drawer hidden>
  <div class="absolute inset-0 bg-black/45 opacity-0 transition-opacity duration-300" data-cart-backdrop data-cart-close></div>
  <aside class="absolute right-0 top-0 flex h-full w-[min(400px,90vw)] translate-x-full flex-col bg-white transition-transform duration-300" data-cart-panel aria-label="Shopping cart">
    <div class="flex items-center justify-between border-b border-line px-5 py-[18px]">
      <span class="text-[13px] font-medium uppercase tracking-[1px]">Shopping Cart</span>
      <button class="text-[26px] leading-none text-heading" type="button" data-cart-close aria-label="Close cart">&times;</button>
    </div>
    <div class="flex flex-1 flex-col overflow-hidden" data-cart-body>
      @include('partials.cart-drawer-items', ['items' => $cartItems, 'subtotal' => $cartSubtotal, 'discount' => $cartDiscount, 'couponCode' => $cartCouponCode])
    </div>
  </aside>
</div>

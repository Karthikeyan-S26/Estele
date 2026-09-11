@extends('layouts.app')

@section('meta_title', 'Cart | '.($siteSettings['site_name'] ?? 'Estele'))
@section('meta_description', 'Your shopping bag.')

@section('content')

  <nav class="mx-auto w-full max-w-wrapper px-3 md:px-4 flex flex-wrap items-center gap-1.5 py-4 text-[13px] text-muted" aria-label="Breadcrumb">
    <x-breadcrumb :items="[['label' => 'Cart']]" />
  </nav>

  <div class="mx-auto w-full max-w-wrapper px-3 md:px-4 pb-10 md:pb-[60px]">
    <h1 class="text-[20px] uppercase tracking-[0.5px] md:text-[26px] mb-5">Your Bag</h1>

    @if($items->isEmpty())
      <div class="py-16 text-center">
        <p class="mb-5 text-[14px] text-muted">Your bag is empty.</p>
        <a class="inline-flex items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" href="{{ route('home') }}">Continue Shopping</a>
      </div>
    @else
      <div class="grid grid-cols-1 gap-6 md:grid-cols-[1fr_340px] md:gap-[34px]">
        <div>
          <table class="w-full [&_.cart-td]:align-middle max-md:[&_thead]:hidden max-md:[&_table]:block max-md:[&_tbody]:block max-md:[&_tr]:block max-md:[&_tr]:border-b max-md:[&_tr]:border-line max-md:[&_tr]:py-4 max-md:[&_.cart-td]:block max-md:[&_.cart-td]:border-0 max-md:[&_.cart-td]:py-1.5">
            <thead>
              <tr class="border-b border-line-strong">
                <th class="pb-3 text-left text-[12px] font-medium uppercase tracking-[0.4px] text-muted" scope="col">Product</th>
                <th class="pb-3 text-left text-[12px] font-medium uppercase tracking-[0.4px] text-muted" scope="col">Price</th>
                <th class="pb-3 text-left text-[12px] font-medium uppercase tracking-[0.4px] text-muted" scope="col">Quantity</th>
                <th class="pb-3 text-left text-[12px] font-medium uppercase tracking-[0.4px] text-muted" scope="col">Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($items as $item)
                <tr class="border-b border-line">
                  <td class="cart-td py-4.5">
                    <div class="flex items-center gap-3.5">
                      <a class="aspect-square w-[84px] shrink-0 overflow-hidden bg-placeholder" href="{{ route('products.show', $item->product) }}">
                        @if($item->product->hasMedia('gallery'))
                          <img class="h-full w-full object-cover" src="{{ $item->product->getFirstMediaUrl('gallery', 'card') }}" alt="{{ $item->product->title }}" loading="lazy" width="100" height="100">
                        @endif
                      </a>
                      <div>
                        <a class="mb-1 block text-[14px] transition-colors hover:text-accent" href="{{ route('products.show', $item->product) }}">{{ $item->product->title }}</a>
                        @if($item->variant)
                          <p class="mb-1.5 text-[12px] text-muted">{{ collect($item->variant->attributes ?? [])->map(fn($v, $k) => "{$k}: {$v}")->implode(', ') }}</p>
                        @endif
                        <form action="{{ route('cart.destroy', $item) }}" method="post">
                          @csrf
                          @method('delete')
                          <button class="text-[12px] text-muted underline transition-colors hover:text-[#eb001b]" type="submit">Remove</button>
                        </form>
                      </div>
                    </div>
                  </td>
                  <td class="cart-td py-4.5 text-[14px]" data-label="Price">₹{{ number_format($item->unitPrice(), 0) }}</td>
                  <td class="cart-td py-4.5" data-label="Qty">
                    <form action="{{ route('cart.update', $item) }}" method="post" data-cart-qty-form>
                      @csrf
                      @method('patch')
                      <div class="inline-flex items-center border border-line-strong" data-qty>
                        <button class="grid h-11 w-10 place-items-center text-[16px] transition-colors hover:text-accent" type="button" data-qty-minus aria-label="Decrease quantity">&minus;</button>
                        <input class="h-11 w-[46px] border-x border-line-strong text-center text-[14px]" type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->availableStock() }}" aria-label="Quantity">
                        <button class="grid h-11 w-10 place-items-center text-[16px] transition-colors hover:text-accent" type="button" data-qty-plus aria-label="Increase quantity">+</button>
                      </div>
                    </form>
                  </td>
                  <td class="cart-td py-4.5 text-[14px] font-medium" data-label="Total">₹{{ number_format($item->unitPrice() * $item->quantity, 0) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>

          <div class="mt-6 max-w-[460px]">
            <label class="mb-1.5 block text-[13px] font-medium text-heading" for="order-note">Order note</label>
            <textarea class="w-full border border-line-strong bg-white px-4 py-3 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-heading" id="order-note" rows="3" placeholder="Add a note to your order"></textarea>
          </div>
        </div>

        <aside class="rounded bg-pinksoft p-6 md:sticky md:top-[100px]">
          <h2 class="mb-4.5 text-[15px] uppercase tracking-[0.5px]">Order Summary</h2>
          <dl class="mb-4.5 space-y-2 text-[14px]">
            <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>₹{{ number_format($subtotal, 0) }}</dd></div>
            @if($discount > 0)
              <div class="flex justify-between text-[#1a7d3f]"><dt>Discount ({{ $couponCode }})</dt><dd>&minus;₹{{ number_format($discount, 0) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-muted">Shipping{{ ($shipping['estimated'] ?? false) ? ' (estimated)' : '' }}</dt><dd>{{ $shipping['fee'] > 0 ? '₹'.number_format($shipping['fee'], 0) : 'Free' }}</dd></div>
            <div class="flex justify-between border-t border-line-strong pt-3.5 text-[16px] font-medium"><dt class="text-heading">Total</dt><dd>₹{{ number_format($subtotal - $discount + $shipping['fee'], 0) }}</dd></div>
          </dl>
          @include('partials.offers-banner')

          <div class="mb-4.5">
            <div class="mb-1.5 flex items-center justify-between">
              <label class="text-[13px] font-medium text-heading" for="coupon">Coupon code</label>
              <button class="text-[12px] text-muted underline transition-colors hover:text-accent" type="button" data-coupons-modal-open>View all coupons</button>
            </div>
            @if($couponCode)
              <div class="flex items-center justify-between border border-line-strong bg-white px-4 py-3 text-[14px]">
                <span>Applied: <strong>{{ $couponCode }}</strong></span>
                <form action="{{ route('cart.coupon.remove') }}" method="post">
                  @csrf
                  @method('delete')
                  <button class="text-[12px] text-muted underline transition-colors hover:text-[#eb001b]" type="submit">Remove</button>
                </form>
              </div>
            @else
              <form action="{{ route('cart.coupon.apply') }}" method="post" class="flex gap-2">
                @csrf
                <input class="w-full border border-line-strong bg-white px-4 py-3 text-[14px] outline-none transition-colors placeholder:text-muted focus:border-heading flex-1" id="coupon" name="code" type="text" placeholder="Enter code" required>
                <button class="inline-flex items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark px-5 py-3 text-[12px]" type="submit">Apply</button>
              </form>
            @endif
          </div>
          <a class="inline-flex items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark w-full" href="{{ route('checkout.index') }}">Proceed to Checkout</a>
          <a class="mt-3.5 block w-fit border-b border-current text-[13px] text-muted mx-auto" href="{{ route('home') }}">Continue Shopping</a>
        </aside>
      </div>
    @endif
  </div>

@endsection

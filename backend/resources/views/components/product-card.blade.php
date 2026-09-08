@props(['product'])

@php
  $rating = $product->reviewsAverageRating();
  $reviewCount = $product->reviewsCount();
  $discount = $product->compare_at_price
    ? (int) round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100)
    : 0;
@endphp

<article class="product-card group">
  <a class="product-card__frame block" href="{{ route('products.show', $product) }}" aria-label="{{ $product->title }}">
    @if($product->hasMedia('gallery'))
      <img class="product-card__img" src="{{ $product->getFirstMediaUrl('gallery', 'card') }}" alt="{{ $product->title }}" loading="lazy" width="600" height="600">
      @if($product->getMedia('gallery')->count() > 1)
        <img class="product-card__img absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100" src="{{ $product->getMedia('gallery')[1]->getUrl('card') }}" alt="" loading="lazy" width="600" height="600">
      @endif
    @endif

    @if($discount > 0)
      <span class="absolute left-2 top-2 z-[2] rounded-md bg-salebadge px-2 py-1 text-[9.5px] font-bold uppercase leading-none tracking-[0.06em] text-white md:text-[10.5px]">{{ $discount }}% off</span>
    @endif

    <button class="absolute right-2 top-2 z-[2] grid h-8 w-8 place-items-center rounded-full bg-white/95 text-heading shadow-sm transition-colors hover:text-rose md:h-9 md:w-9" type="button" aria-label="Save {{ $product->title }} to wishlist" data-wishlist-toggle data-product-id="{{ $product->id }}">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21.2l7.7-7.7 1.1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
    </button>
  </a>

  <div class="flex flex-1 flex-col px-3 pb-3 pt-1.5 md:px-3.5 md:pb-3.5">
    <h3 class="mb-1.5 line-clamp-2 font-serif text-[13px] font-medium leading-snug text-heading md:text-[14.5px] lg:text-[15px]">
      <a class="transition-colors hover:text-rose" href="{{ route('products.show', $product) }}">{{ $product->title }}</a>
    </h3>
    @if($reviewCount > 0)
      <div class="mb-1.5">
        <x-review-stars :rating="$rating" :count="$reviewCount" size="text-[11px] md:text-[12px]" />
      </div>
    @endif
    <div class="mb-2.5 flex flex-wrap items-baseline gap-x-1.5 md:mb-3">
      <span class="text-[14px] font-bold text-price md:text-[15.5px] lg:text-[16.5px]">₹{{ number_format($product->price, 0) }}</span>
      @if($product->compare_at_price)
        <span class="text-[11px] text-muted line-through md:text-[12.5px]">₹{{ number_format($product->compare_at_price, 0) }}</span>
      @endif
    </div>
    <form class="mt-auto" action="{{ route('cart.store', $product) }}" method="post" data-cart-form data-checkout-url="{{ route('checkout.index') }}">
      @csrf
      <input type="hidden" name="quantity" value="1">
      <button class="flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-heading px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.06em] text-white transition-colors hover:bg-rose md:px-3 md:py-2.5 md:text-[11px] md:tracking-[0.12em]" type="submit">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 7h15l-1.5 8h-12z"/><path d="M6 7 5 3H2"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
        Add to cart
      </button>
    </form>
  </div>
</article>

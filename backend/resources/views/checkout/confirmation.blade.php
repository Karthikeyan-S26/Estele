@extends('layouts.app')

@section('meta_title', 'Order Confirmed | '.($siteSettings['site_name'] ?? 'Estele'))

@section('content')

  <div class="mx-auto w-full max-w-[640px] px-3 py-12 text-center md:px-4 md:py-16">
    <div class="mx-auto mb-5 grid h-14 w-14 place-items-center rounded-full bg-pinksoft text-accent">
      <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
    </div>
    <h1 class="mb-2 text-[22px] md:text-[28px]">Thank you, {{ $order->customer_name }}!</h1>
    <p class="mb-1 text-[14px] text-muted">Your order has been placed successfully.</p>
    <p class="mb-8 text-[14px] font-medium text-heading">Order Number: {{ $order->order_number }}</p>

    <div class="mb-8">
      <x-orders.summary :order="$order" />
    </div>

    <div class="flex flex-wrap items-center justify-center gap-3">
      @auth
        @if($order->user_id === auth()->id())
          <a class="inline-flex items-center justify-center gap-2 border border-line-strong bg-white px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-heading transition-colors hover:border-heading" href="{{ route('account.orders.show', $order) }}">
            View Order
          </a>
        @endif
      @endauth
      <a class="inline-flex items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" href="{{ route('home') }}">
        Continue Shopping
      </a>
    </div>
  </div>

@endsection

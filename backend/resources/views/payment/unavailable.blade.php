@extends('layouts.app')

@section('meta_title', 'Payment Unavailable | '.($siteSettings['site_name'] ?? 'Estele'))

@section('content')

  <div class="mx-auto w-full max-w-[640px] px-3 py-12 text-center md:px-4 md:py-16">
    <h1 class="mb-2 text-[22px] md:text-[28px]">Payment temporarily unavailable</h1>
    <p class="mb-1 text-[14px] text-muted">Your order has been placed and your items are reserved — Order Number: <strong>{{ $order->order_number }}</strong></p>
    <p class="mb-8 text-[14px] text-muted">We couldn't reach the payment gateway just now. Please try again in a moment.</p>

    <a class="inline-flex items-center justify-center gap-2 border border-accent bg-accent px-8 py-[13px] text-[13px] font-medium uppercase tracking-[0.5px] text-white transition-colors hover:border-accent-dark hover:bg-accent-dark" href="{{ route('payment.show', $order) }}">
      Try Again
    </a>
  </div>

@endsection

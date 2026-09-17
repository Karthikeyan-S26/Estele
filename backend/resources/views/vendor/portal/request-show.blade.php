@extends('layouts.vendor')

@section('meta_title', $request->request_number.' | Vendor Portal | Estele')

@section('content')

  @php
    $isOpen = $invitation->response_status === 'pending'
        && $request->status === 'bidding_active'
        && now()->lessThan($invitation->expires_at);
  @endphp

  <a href="{{ route('vendor.dashboard') }}" class="mb-4 inline-flex items-center gap-1.5 text-[12px] font-medium text-muted hover:text-accent">
    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
    Back to dashboard
  </a>

  <h1 class="mb-1 font-display text-[20px] uppercase tracking-[0.5px] text-heading">{{ $request->request_number }}</h1>
  <p class="mb-6 text-[13px] text-muted">Bidding closes {{ $request->bidding_end_at?->formatIst('d M Y, h:i A') }}</p>

  <div class="mb-5 rounded-xl border border-line bg-white p-4 sm:p-5">
    @if ($request->hasMedia('image'))
      <img class="mb-3 max-h-72 w-full rounded-lg object-cover" src="{{ route('vendor.requests.image', $request) }}" alt="Jewellery photo">
    @endif

    @if ($request->hasMedia('video'))
      <video class="mb-3 w-full rounded-lg" controls preload="none" src="{{ route('vendor.requests.video', $request) }}"></video>
    @endif

    @if ($request->description)
      <p class="text-[13px] leading-relaxed text-muted">{{ $request->description }}</p>
    @endif
  </div>

  @if ($isOpen)
    <div class="mb-5 rounded-xl border border-line bg-white p-4 sm:p-5">
      <h2 class="mb-3 text-[13px] font-medium uppercase tracking-[0.3px] text-heading">Place your bid</h2>
      <form action="{{ route('vendor.requests.bid', $request) }}" method="post" class="flex flex-col gap-3 sm:flex-row">
        @csrf
        <input class="h-12 flex-1 rounded-lg border border-line-strong px-4 text-[14px] outline-none focus:border-accent focus:ring-1 focus:ring-accent" type="number" name="amount" min="1" max="1000000" step="0.01" placeholder="Your bid (₹)" required>
        <button class="h-12 shrink-0 rounded-lg bg-accent px-6 text-[12.5px] font-semibold uppercase tracking-[0.5px] text-white transition-colors hover:bg-accent-dark" type="submit">
          Submit Bid
        </button>
      </form>
    </div>

    <details class="rounded-xl border border-line bg-white p-4 sm:p-5">
      <summary class="cursor-pointer text-[12.5px] font-medium uppercase tracking-[0.3px] text-muted">Can't offer on this one?</summary>
      <form class="mt-3" action="{{ route('vendor.requests.decline', $request) }}" method="post">
        @csrf
        <textarea class="mb-3 w-full rounded-lg border border-line p-3 text-[13px]" name="reason" rows="3" maxlength="1000" placeholder="Reason (optional)"></textarea>
        <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-line px-6 py-2.5 text-[12px] font-medium uppercase tracking-[0.5px] text-heading transition-colors hover:border-salebadge hover:text-salebadge sm:w-auto" type="submit">
          Decline
        </button>
      </form>
    </details>
  @elseif ($invitation->response_status === 'accepted')
    <div class="rounded-xl border border-line bg-pinksoft p-4 text-[13px] text-heading">
      You submitted a bid on {{ $invitation->responded_at?->formatIst('d M Y, h:i A') }}.
    </div>
  @elseif ($invitation->response_status === 'declined')
    <div class="rounded-xl border border-line bg-white p-4 text-[13px] text-muted">
      You declined this request{{ $invitation->decline_reason ? ' — "'.$invitation->decline_reason.'"' : '' }}.
    </div>
  @else
    <div class="rounded-xl border border-line bg-white p-4 text-[13px] text-muted">
      This bidding window has closed.
    </div>
  @endif

@endsection

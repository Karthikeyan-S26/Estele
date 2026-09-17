@extends('layouts.vendor')

@section('meta_title', 'Dashboard | Vendor Portal | Estele')

@section('content')

  <div class="mb-6 rounded-xl border border-line bg-white p-5 sm:p-6">
    <h1 class="font-display text-[20px] uppercase tracking-[0.5px] text-heading">Welcome back, {{ $vendor->name }}</h1>
    <p class="mt-1 text-[13px] text-muted">Here's what's happening with your bidding account today.</p>
  </div>

  <div class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
    <div class="rounded-xl border border-line bg-white p-4">
      <span class="grid h-8 w-8 place-items-center rounded-full bg-pinksoft text-accent">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
      </span>
      <p class="mt-2 text-[11px] uppercase tracking-[0.3px] text-muted">Open to Bid</p>
      <p class="mt-0.5 font-display text-[22px] text-heading">{{ $stats['open'] }}</p>
    </div>
    <div class="rounded-xl border border-line bg-white p-4">
      <span class="grid h-8 w-8 place-items-center rounded-full bg-pinksoft text-accent">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      </span>
      <p class="mt-2 text-[11px] uppercase tracking-[0.3px] text-muted">Bids Submitted</p>
      <p class="mt-0.5 font-display text-[22px] text-heading">{{ $stats['submitted'] }}</p>
    </div>
    <div class="rounded-xl border border-line bg-white p-4">
      <span class="grid h-8 w-8 place-items-center rounded-full bg-gold-light text-gold-hover">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
      </span>
      <p class="mt-2 text-[11px] uppercase tracking-[0.3px] text-muted">Won</p>
      <p class="mt-0.5 font-display text-[22px] text-heading">{{ $stats['won'] }}</p>
    </div>
    <div class="rounded-xl border border-line bg-white p-4">
      <span class="grid h-8 w-8 place-items-center rounded-full bg-gold-light text-gold-hover">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H7"/></svg>
      </span>
      <p class="mt-2 text-[11px] uppercase tracking-[0.3px] text-muted">Won Value</p>
      <p class="mt-0.5 font-display text-[22px] text-heading">₹{{ number_format((float) $stats['wonValue'], 0) }}</p>
    </div>
  </div>

  <div class="mb-4 flex items-center justify-between">
    <h2 class="text-[14px] font-medium uppercase tracking-[0.4px] text-heading">Open Requests to Bid On</h2>
    <a href="{{ route('vendor.bids') }}" class="text-[12px] font-medium text-accent hover:text-accent-dark">Full history →</a>
  </div>

  @if ($openInvitations->isEmpty())
    <div class="rounded-xl border border-dashed border-line bg-white p-8 text-center">
      <span class="mx-auto mb-3 grid h-10 w-10 place-items-center rounded-full bg-greysoft text-muted">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      </span>
      <p class="text-[13px] text-muted">No open requests right now.</p>
      <p class="mt-1 text-[12px] text-muted">You'll be notified here and by email as soon as a customer submits one.</p>
    </div>
  @else
    <div class="space-y-3">
      @foreach ($openInvitations as $invitation)
        @php($jewelleryRequest = $invitation->request)
        <a href="{{ route('vendor.requests.show', $jewelleryRequest) }}" class="flex items-center gap-4 rounded-xl border border-line bg-white p-4 transition-colors hover:border-accent hover:shadow-sm">
          <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-greysoft">
            @if ($jewelleryRequest->getFirstMediaUrl('image', 'thumb'))
              <img class="h-full w-full object-cover" src="{{ $jewelleryRequest->getFirstMediaUrl('image', 'thumb') }}" alt="">
            @endif
          </div>
          <div class="min-w-0 flex-1">
            <p class="truncate text-[14px] font-medium text-heading">{{ $jewelleryRequest->request_number }}</p>
            <p class="truncate text-[12.5px] text-muted">{{ $jewelleryRequest->description ?: 'No description provided' }}</p>
            <p class="mt-1 inline-flex items-center gap-1 text-[11.5px] font-medium text-accent">
              <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
              Closes {{ $invitation->expires_at?->formatIst('d M, h:i A') }}
            </p>
          </div>
          <svg class="h-4 w-4 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
        </a>
      @endforeach
    </div>
  @endif

@endsection

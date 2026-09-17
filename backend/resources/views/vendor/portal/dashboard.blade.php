@extends('layouts.vendor')

@section('meta_title', 'Dashboard | Vendor Portal | Estele')

@section('content')

  <h1 class="mb-1 font-display text-[20px] uppercase tracking-[0.5px] text-heading">Dashboard</h1>
  <p class="mb-6 text-[13px] text-muted">Welcome back, {{ $vendor->name }}.</p>

  <div class="mb-8 grid grid-cols-3 gap-3 sm:gap-4">
    <div class="rounded-xl border border-line bg-white p-4">
      <p class="text-[11px] uppercase tracking-[0.3px] text-muted">Open to bid</p>
      <p class="mt-1 font-display text-[24px] text-heading">{{ $stats['open'] }}</p>
    </div>
    <div class="rounded-xl border border-line bg-white p-4">
      <p class="text-[11px] uppercase tracking-[0.3px] text-muted">Bids submitted</p>
      <p class="mt-1 font-display text-[24px] text-heading">{{ $stats['submitted'] }}</p>
    </div>
    <div class="rounded-xl border border-line bg-white p-4">
      <p class="text-[11px] uppercase tracking-[0.3px] text-muted">Won</p>
      <p class="mt-1 font-display text-[24px] text-accent">{{ $stats['won'] }}</p>
    </div>
  </div>

  <div class="mb-4 flex items-center justify-between">
    <h2 class="text-[14px] font-medium uppercase tracking-[0.4px] text-heading">Open requests to bid on</h2>
    <a href="{{ route('vendor.bids') }}" class="text-[12px] font-medium text-accent hover:text-accent-dark">Full history →</a>
  </div>

  @if ($openInvitations->isEmpty())
    <div class="rounded-xl border border-line bg-white p-6 text-center text-[13px] text-muted">
      No open requests right now. You'll be notified here and by email as soon as a customer submits one.
    </div>
  @else
    <div class="space-y-3">
      @foreach ($openInvitations as $invitation)
        @php($jewelleryRequest = $invitation->request)
        <a href="{{ route('vendor.requests.show', $jewelleryRequest) }}" class="flex items-center gap-4 rounded-xl border border-line bg-white p-4 transition-colors hover:border-accent">
          <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-greysoft">
            @if ($jewelleryRequest->getFirstMediaUrl('image', 'thumb'))
              <img class="h-full w-full object-cover" src="{{ $jewelleryRequest->getFirstMediaUrl('image', 'thumb') }}" alt="">
            @endif
          </div>
          <div class="min-w-0 flex-1">
            <p class="truncate text-[14px] font-medium text-heading">{{ $jewelleryRequest->request_number }}</p>
            <p class="truncate text-[12.5px] text-muted">{{ $jewelleryRequest->description ?: 'No description provided' }}</p>
            <p class="mt-1 text-[11.5px] text-accent">Closes {{ $invitation->expires_at?->formatIst('d M, h:i A') }}</p>
          </div>
          <svg class="h-4 w-4 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
        </a>
      @endforeach
    </div>
  @endif

@endsection

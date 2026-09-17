@extends('layouts.vendor')

@section('meta_title', 'Bids & Settlements | Vendor Portal | Estele')

@section('content')

  <h1 class="mb-1 font-display text-[20px] uppercase tracking-[0.5px] text-heading">Bids &amp; Settlements</h1>
  <p class="mb-6 text-[13px] text-muted">Every bid you've placed, and what happened to the ones you won.</p>

  @if ($bids->isEmpty())
    <div class="rounded-xl border border-line bg-white p-6 text-center text-[13px] text-muted">
      You haven't placed a bid yet.
    </div>
  @else
    <div class="space-y-3">
      @foreach ($bids as $bid)
        @php
          $jewelleryRequest = $bid->request;
          $won = $jewelleryRequest?->winning_bid_id === $bid->id;
          $decided = $jewelleryRequest && ! in_array($jewelleryRequest->status, ['pending', 'submitted', 'vendors_notified', 'bidding_active', 'cancelled'], true);
          $status = match (true) {
              ! $jewelleryRequest => 'Unavailable',
              $won => 'Won',
              $decided => 'Lost',
              default => 'Pending',
          };
          $statusColor = match ($status) {
              'Won' => 'bg-pinksoft text-accent-dark',
              'Lost' => 'bg-greysoft text-muted',
              default => 'bg-gold-light text-heading',
          };
          $credit = $jewelleryRequest?->walletCredit;
        @endphp
        <div class="rounded-xl border border-line bg-white p-4">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[14px] font-medium text-heading">
                @if ($jewelleryRequest)
                  <a href="{{ route('vendor.requests.show', $jewelleryRequest) }}" class="hover:text-accent">{{ $jewelleryRequest->request_number }}</a>
                @else
                  Request removed
                @endif
              </p>
              <p class="text-[11.5px] text-muted">Bid on {{ $bid->submitted_at?->formatIst('d M Y, h:i A') }}</p>
            </div>
            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.3px] {{ $statusColor }}">{{ $status }}</span>
          </div>

          <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-line pt-3 text-[13px]">
            <div>
              <span class="text-muted">Your bid</span>
              <span class="ml-1.5 font-medium text-price">₹{{ number_format((float) $bid->amount, 2) }}</span>
            </div>

            @if ($won && $credit)
              <div>
                <span class="text-muted">Customer credited</span>
                <span class="ml-1.5 font-medium text-price">₹{{ number_format((float) $credit->credited_amount, 2) }}</span>
              </div>
              <div>
                <span class="text-muted">Settlement status</span>
                <span class="ml-1.5 font-medium text-heading">{{ \Illuminate\Support\Str::headline($credit->status) }}</span>
              </div>
            @elseif ($won)
              <div class="text-muted">Settlement is being processed.</div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @endif

@endsection

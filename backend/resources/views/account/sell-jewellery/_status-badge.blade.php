@php
  $statusClasses = [
      'pending' => 'bg-amber-100 text-amber-700',
      'submitted' => 'bg-amber-100 text-amber-700',
      'vendors_notified' => 'bg-green-100 text-green-700',
      'bidding_active' => 'bg-green-100 text-green-700',
      'bidding_closed' => 'bg-amber-100 text-amber-700',
      'bid_selected' => 'bg-amber-100 text-amber-700',
      'wallet_pending' => 'bg-amber-100 text-amber-700',
      'wallet_credited' => 'bg-green-100 text-green-700',
      'wallet_expired' => 'bg-red-100 text-red-700',
      'completed' => 'bg-emerald-100 text-emerald-800',
      'cancelled' => 'bg-red-100 text-red-800',
  ];
  $statusLabels = [
      'pending' => 'Waiting for Approval',
      'submitted' => 'Waiting for Approval',
      'vendors_notified' => 'Approved',
      'bidding_active' => 'Approved',
      'bidding_closed' => 'Bidding Closed',
      'bid_selected' => 'Bid Selected',
      'wallet_pending' => 'Wallet Pending',
      'wallet_credited' => 'Wallet Credited',
      'wallet_expired' => 'Wallet Expired',
      'completed' => 'Completed',
      'cancelled' => 'Cancelled',
  ];
  $badgeClass = $statusClasses[$request->status] ?? 'bg-gray-100 text-gray-700';
  $badgeLabel = $statusLabels[$request->status] ?? $request->status;
@endphp

@if (($asCard ?? false))
  <a href="{{ route('account.sell-jewellery.show', $request) }}" class="flex items-center gap-3 rounded-lg border border-line p-4 transition-colors hover:border-accent hover:shadow-sm">
    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-md bg-greysoft">
      @if ($request->getFirstMediaUrl('image', 'thumb'))
        <img class="h-full w-full object-cover" src="{{ $request->getFirstMediaUrl('image', 'thumb') }}" alt="">
      @endif
    </div>
    <div class="min-w-0 flex-1">
      <div class="mb-1.5 flex items-center justify-between gap-2">
        <span class="truncate text-[13px] font-medium text-heading">{{ $request->request_number }}</span>
        <span class="shrink-0 text-[11px] text-muted">{{ $request->created_at->formatIst('d M Y') }}</span>
      </div>
      <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium uppercase tracking-[0.3px] {{ $badgeClass }}">{{ $badgeLabel }}</span>
      @if ($request->final_amount)
        <span class="ml-2 text-[13px] font-medium text-heading">₹{{ number_format((float) $request->final_amount, 2) }}</span>
      @endif
    </div>
  </a>
@else
  <span class="rounded-full px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-[0.3px] {{ $badgeClass }}">{{ $badgeLabel }}</span>
@endif
